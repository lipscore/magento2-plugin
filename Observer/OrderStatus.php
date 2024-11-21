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
    protected static $logFile = 'ls_order_status_observer';

    protected $reminder;

    public function __construct(
        Config $config,
        Logger $logger,
        Reminder $reminder
    ) {
        parent::__construct($config, $logger);
        $this->reminder = $reminder;
    }

    protected function _execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');

        if (!$this->config->isLipscoreModuleEnabled()) {
            $this->log('extension is disabled');
            return;
        }

        if (!$this->config->getApiKey()) {
            $this->log('API key is invalid');
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

    protected function methodAvailable()
    {
        return true;
    }
}
