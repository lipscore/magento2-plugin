<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Lipscore\RatingsReviews\Model\Config\AbstractConfig;

class Admin extends AbstractConfig
{
    public function __construct(
        \Lipscore\RatingsReviews\Model\Config\AccessorFactory $configAccessorFactory,
        $websiteId,
        $storeId
    ){
        $this->websiteId = $websiteId;
        $this->storeId   = $storeId;
        # order is important, should be affter website and store setters
        parent::__construct($configAccessorFactory);
    }
}
