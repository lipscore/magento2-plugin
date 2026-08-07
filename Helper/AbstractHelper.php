<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractHelper
{
    /**
     * @var mixed
     */
    protected $store;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Initialize dependencies.
     *
     * @param Logger $logger
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
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
