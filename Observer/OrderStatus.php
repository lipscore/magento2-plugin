<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder;
use Lipscore\RatingsReviews\Model\Invitation\Scheduler;
use Lipscore\RatingsReviews\Model\Invitation\Source;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
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
     * @deprecated 2.3.0 Kept for constructor compatibility, scheduling moved to Scheduler.
     * @see Scheduler
     */
    protected $reminder;

    /** @var InvitationResource */
    protected $invitationResource;

    /** @var Scheduler */
    protected $scheduler;

    /** @var OrderResource */
    protected $orderResource;

    /** @var int|null */
    protected $storeId;

    /**
     * @param Config $config
     * @param Logger $logger
     * @param Reminder $reminder
     * @param InvitationResource|null $invitationResource
     * @param Scheduler|null $scheduler
     * @param OrderResource|null $orderResource
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Reminder $reminder,
        ?InvitationResource $invitationResource = null,
        ?Scheduler $scheduler = null,
        ?OrderResource $orderResource = null
    ) {
        parent::__construct($config, $logger);
        $this->reminder = $reminder;
        $objectManager = ObjectManager::getInstance();
        $this->invitationResource = $invitationResource
            ?: $objectManager->get(InvitationResource::class);
        $this->scheduler = $scheduler ?: $objectManager->get(Scheduler::class);
        $this->orderResource = $orderResource ?: $objectManager->get(OrderResource::class);
    }

    /**
     * Schedule the invitation on status change.
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
     * Schedule the invitation and record the outcome.
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

        if ($this->isAlreadyScheduled($order)) {
            $this->log('already scheduled');
            return;
        }

        $this->log('SCHEDULE!');
        $result = $this->scheduler->schedule([$order], Source::EVENT);
        $this->log('result status: ' . (int) $result->getOrderStatus($order->getId()));
    }

    /**
     * Whether the invitation is scheduled; before setup:upgrade creates the table, it counts as not scheduled.
     *
     * @param Order $order
     * @return bool
     */
    protected function isAlreadyScheduled(Order $order)
    {
        try {
            return $this->invitationResource->isScheduled($order->getId());
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    /**
     * Whether invitations are scheduled for the status.
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
