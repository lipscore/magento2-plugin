<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;

class OrderStatus extends AbstractObserver
{
    /**
     * @var string
     */
    protected static $logFile = 'ls_order_status_observer';

    /**
     * @var Reminder
     */
    protected $reminder;

    /**
     * Initialize observer dependencies.
     *
     * @param Config $config
     * @param Logger $logger
     * @param Reminder $reminder
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Reminder $reminder
    ) {
        parent::__construct($config, $logger);
        $this->reminder = $reminder;
    }

    /**
     * Send a review reminder when the order status changes to a configured status.
     *
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');

        if (!$this->checkIfEnabled($order->getStoreId())) {
            return;
        }

        $statusChanged = $order->dataHasChangedFor(OrderInterface::STATUS);
        $this->log('status changed: ' . (int) $statusChanged);
        if (!$statusChanged) {
            return;
        }

        $currentStatus = $order->getStatus();
        $this->log('current status: ' . $currentStatus);

        $properStatus = $this->isReminderableStatus($currentStatus);
        $this->log('proper status: ' . (int) $properStatus);
        if ($properStatus) {
            $this->log('SEND!');
            $result = $this->reminder->send($order);
            $this->log($result);
        }
    }

    /**
     * Check whether the given status is configured to trigger a reminder.
     *
     * @param string $status
     * @return bool
     */
    protected function isReminderableStatus($status)
    {
        $reminderableStatus = $this->config->getEmailsOrderStatus();
        $this->log('reminderable status: ' . $reminderableStatus);
        if (!$reminderableStatus) {
            return false;
        } else {
            return strtolower($status) == strtolower($reminderableStatus);
        }
    }

    /**
     * Check whether the observer method is available to run.
     *
     * @return bool
     */
    protected function methodAvailable()
    {
        return true;
    }
}
