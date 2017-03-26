<?php

namespace Lipscore\RatingsReviews\Helper;

abstract class AbstractHelper
{
    protected $store;

    protected $lipscoreConfig;
    protected $storeManager;
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->lipscoreConfig = $config;
        $this->storeManager   = $storeManager;
        $this->logger         = $logger;
    }

    public function getStore()
    {
        return $this->storeManager->getStore($this->lipscoreConfig->storeId);
    }
}
