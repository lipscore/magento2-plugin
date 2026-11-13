<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder;
use Lipscore\RatingsReviews\Model\Reminder\OrderSender;
use Lipscore\RatingsReviews\Model\Reminder\Source;
use Lipscore\RatingsReviews\Model\ResourceModel\OrderReminder as OrderReminderResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

/**
 * sales_order_save_after runs inside the order transaction, so the status change is detected here
 * but the API request waits for the commit: no locks held during HTTP, nothing sent on rollback.
 */
class OrderStatus extends AbstractObserver
{
    /** @var string */
    protected static $logFile = 'ls_order_status_observer';

    /**
     * @var Reminder
     * @deprecated 2.3.0 Kept for constructor compatibility, sending moved to OrderSender.
     * @see OrderSender
     */
    protected $reminder;

    /** @var OrderReminderResource */
    protected $orderReminderResource;

    /** @var OrderSender */
    protected $orderSender;

    /** @var OrderResource */
    protected $orderResource;

    /** @var int|null */
    protected $storeId;

    /**
     * @param Config $config
     * @param Logger $logger
     * @param Reminder $reminder
     * @param OrderReminderResource|null $orderReminderResource
     * @param OrderSender|null $orderSender
     * @param OrderResource|null $orderResource
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Reminder $reminder,
        ?OrderReminderResource $orderReminderResource = null,
        ?OrderSender $orderSender = null,
        ?OrderResource $orderResource = null
    ) {
        parent::__construct($config, $logger);
        $this->reminder = $reminder;
        $objectManager = ObjectManager::getInstance();
        $this->orderReminderResource = $orderReminderResource
            ?: $objectManager->get(OrderReminderResource::class);
        $this->orderSender = $orderSender ?: $objectManager->get(OrderSender::class);
        $this->orderResource = $orderResource ?: $objectManager->get(OrderResource::class);
    }

    /**
     * Schedule the review request on status change.
     *
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $this->storeId = $order->getStoreId();

        if (!$this->checkIfEnabled($order->getStoreId())) {
            return;
        }

        if (!$this->config->isEmailsEventTriggerEnabled($order->getStoreId())) {
            $this->log('order save trigger disabled, scheduled check is used');
            return;
        }

        $statusChanged = $order->dataHasChangedFor(OrderInterface::STATUS);
        $this->log('status changed: ' . (int) $statusChanged);
        if (!$statusChanged) {
            return;
        }

        $currentStatus = $order->getStatus();
        $this->log('current status: ' . $currentStatus);

        $properStatus = $this->isReminderableStatus($currentStatus, $order->getStoreId());
        $this->log('proper status: ' . (int) $properStatus);
        if ($properStatus) {
            $this->sendAfterCommit($order);
        }
    }

    /**
     * Send after the order transaction commits.
     *
     * @param Order $order
     * @return void
     */
    protected function sendAfterCommit(Order $order)
    {
        $callback = function () use ($order) {
            try {
                $this->send($order);
            } catch (\Throwable $e) {
                $this->logger->log($e);
            }
        };

        if ($this->orderResource->getConnection()->getTransactionLevel() > 0) {
            $this->log('waiting for commit');
            $this->orderResource->addCommitCallback($callback);
            return;
        }

        $callback();
    }

    /**
     * Send and record the outcome.
     *
     * @param Order $order
     * @return void
     */
    protected function send(Order $order)
    {
        // The status may have changed again before the commit.
        if (!$this->isReminderableStatus($order->getStatus(), $order->getStoreId())) {
            $this->log('status changed before commit');
            return;
        }

        if ($this->isAlreadySent($order)) {
            $this->log('already sent');
            return;
        }

        $this->log('SEND!');
        try {
            $result = $this->orderSender->send($order);
        } catch (\Throwable $e) {
            $this->logger->log($e);
            $this->markFailed($order, $e->getMessage());
            return;
        }
        $this->log($result);

        if ($result === false) {
            $this->markFailed($order, 'Lipscore API request was not successful.');
            return;
        }

        $this->markSent($order);
    }

    /**
     * Whether the order was sent; before setup:upgrade creates the table, it counts as not sent.
     *
     * @param Order $order
     * @return bool
     */
    protected function isAlreadySent(Order $order)
    {
        try {
            return $this->orderReminderResource->isSent($order->getId());
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    /**
     * Record a sent request.
     *
     * @param Order $order
     * @return void
     */
    protected function markSent(Order $order)
    {
        try {
            $this->orderReminderResource->markSent(
                $order->getId(),
                $order->getStoreId(),
                Source::EVENT
            );
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }

    /**
     * Record a failed request.
     *
     * @param Order $order
     * @param string $error
     * @return void
     */
    protected function markFailed(Order $order, $error)
    {
        try {
            $this->orderReminderResource->markFailed(
                $order->getId(),
                $order->getStoreId(),
                Source::EVENT,
                $error
            );
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }

    /**
     * Whether review requests are sent for the status.
     *
     * @param string $status
     * @param int|null $storeId
     * @return bool
     */
    protected function isReminderableStatus($status, $storeId = null)
    {
        $reminderableStatus = $this->config->getEmailsOrderStatus($storeId ?? $this->storeId);
        $this->log('reminderable status: ' . $reminderableStatus);
        if (!$reminderableStatus) {
            return false;
        } else {
            return strtolower((string) $status) == strtolower($reminderableStatus);
        }
    }

    /**
     * @inheritDoc
     */
    protected function methodAvailable()
    {
        return true;
    }
}
