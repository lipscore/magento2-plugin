<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractHelper
{
    protected $store;

    protected $config;

    protected $storeManager;

    protected $logger;

    public function __construct(
        Logger $logger,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager   = $storeManager;
        $this->logger         = $logger;
    }
}
