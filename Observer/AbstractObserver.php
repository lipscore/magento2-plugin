<?php

namespace Lipscore\RatingsReviews\Observer;

use Magento\Framework\Event\ObserverInterface;

abstract class AbstractObserver implements ObserverInterface
{
    protected static $logFile = 'observer';
    protected static $logEnabled = false;

    protected $_moduleHelper;
    protected $moduleHelperFactory;
    protected $adminConfigFactory;
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Helper\ModuleFactory $moduleHelperFactory,
        \Lipscore\RatingsReviews\Model\Config\AdminFactory $adminConfigFactory
    ){
        $this->moduleHelperFactory = $moduleHelperFactory;
        $this->adminConfigFactory  = $adminConfigFactory;
        $this->logger              = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            $this->log($this->defaultLogMessage());
            if ($this->methodAvailable()) {
                return $this->_execute($observer);
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }

    abstract protected function methodAvailable();

    protected function moduleHelper()
    {
        if (!$this->_moduleHelper) {
            $this->_moduleHelper = $this->moduleHelperFactory->create();
        }
        return $this->_moduleHelper;
    }

    protected function defaultLogMessage()
    {
        return get_class($this);
    }

    protected function log($message)
    {
        if (!getenv('LIPSCORE_LOG_OBSERVER') && !static::$logEnabled) {
            return;
        }

        $filePath = BP . '/var/log/' . static::$logFile . '.log';
        $time     = date('d-m-Y H:i:s O');
        $message  = "$time " . print_r($message, true) . "\n";
        file_put_contents($filePath, $message, FILE_APPEND);
    }
}
