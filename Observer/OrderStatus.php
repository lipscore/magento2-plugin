<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Observer\AbstractObserver;
use Magento\Sales\Api\Data\OrderInterface;
use \Magento\Framework\Exception\LocalizedException;

class OrderStatus extends AbstractObserver
{
    protected static $logFile = 'ls_order_status_observer';

    protected $reminderFactory;
    protected $data = [];
    protected $_reminder;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Helper\ModuleFactory $moduleHelperFactory,
        \Lipscore\RatingsReviews\Model\Config\AdminFactory $adminConfigFactory,
        \Lipscore\RatingsReviews\Model\ReminderFactory $reminderFactory
    ) {
        parent::__construct($logger, $moduleHelperFactory, $adminConfigFactory);
        $this->reminderFactory = $reminderFactory;
    }

    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');

        $this->init($order->getStoreId());

        if (!$this->isReminderEnabled()) {
            $this->log('extension is disabled');
            return;
        }

        if (!$this->config->isValidApiKey()) {
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
            $result = $this->reminder()->send($order);
            $this->log($result);
        }
    }

    protected function init($storeId)
    {
        $this->data['config'] = $this->adminConfigFactory->create(
            [
                'storeId'   => $storeId,
                'websiteId' => null
            ]
        );
    }

    protected function isReminderableStatus($status)
    {
        $reminderableStatus = $this->config->reminderStatus();
        $this->log('reminderable status: ' . $reminderableStatus);
        if (!$reminderableStatus) {
            return false;
        } else {
            return strtolower($status) == strtolower($reminderableStatus);
        }
    }

    protected function reminder()
    {
        if (!$this->_reminder) {
            $this->_reminder = $this->reminderFactory->create(
                [
                    'config' => $this->config
                ]
            );
        }
        return $this->_reminder;
    }

    protected function isReminderEnabled()
    {
        return $this->moduleHelper()->isLipscoreModuleEnabled();
    }

    protected function moduleHelper()
    {
        if (!$this->_moduleHelper) {
            $this->_moduleHelper = $this->moduleHelperFactory->create(
                [
                    'config' => $this->config
                ]
            );
        }
        return $this->_moduleHelper;
    }

    protected function methodAvailable()
    {
        return true;
    }

    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } elseif ($name == 'config') {
            throw new LocalizedException(__('No config is set'));
        } else {
            throw new LocalizedException(__('Undefined property on ') . get_class($this) . ': ' . $name);
        }
    }
}
