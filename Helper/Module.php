<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;

class Module extends AbstractHelper
{
    const MODULE_NAME = 'Lipscore_RatingsReviews';

    protected $moduleManager;
    protected $moduleResource;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        $data = array()
    ) {
        if (isset($data['config'])) {
            $config = $config;
        }
        parent::__construct($logger, $config, $storeManager);

        $this->moduleManager  = $moduleManager;
        $this->moduleResource = $moduleResource;
    }

    public function getVersion()
    {
        try {
            return (string) $this->moduleResource->getDbVersion(self::MODULE_NAME);
        } catch (\Exception $e) {
            $this->logger->log($e);
            return 'N/A';
        }
    }

    public function isLipscoreActive()
    {
        try {
            return $this->isLipscoreModuleEnabled() && $this->isLipscoreOutputEnabled();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function isLipscoreModuleEnabled()
    {
        try {
            return $this->moduleManager->isEnabled(self::MODULE_NAME) && $this->isLipscoreEnabledByConfig();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function isLipscoreOutputEnabled()
    {
        try {
            return $this->moduleManager->isOutputEnabled(self::MODULE_NAME) && $this->isLipscoreEnabledByConfig();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    protected function isLipscoreEnabledByConfig()
    {
        return $this->lipscoreConfig->isModuleActive();
    }
}
