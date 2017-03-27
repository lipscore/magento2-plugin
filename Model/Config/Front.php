<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Lipscore\RatingsReviews\Model\Config\AbstractConfig;

class Front extends AbstractConfig
{
    public function __construct(
        \Lipscore\RatingsReviews\Model\Config\AccessorFactory $configAccessorFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeId = $storeManager->getStore()->getId();
        // order is important, should be affter website and store setters
        parent::__construct($configAccessorFactory);
    }
}
