<?php

namespace Lipscore\RatingsReviews\Plugin;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\ProductData;
use Magento\Review\Block\Product\Review as MagentoReviewBlock;

class ReviewTabPlugin
{
    protected $productData;

    protected $config;

    public function __construct(
        ProductData $productData,
        Config $config
    ) {
        $this->productData = $productData;
        $this->config = $config;
    }

    public function afterGetData(
        MagentoReviewBlock $subject,
        $result,
        $key
    ) {
        if ($key === 'ls_product_attrs' && $this->config->isActive() && $this->config->canShowReviewTab()) {
            return $this->productData->getCurrentProductLsAttributes();
        }

        return $result;
    }

    public function afterToHtml(
        MagentoReviewBlock $subject,
        $result
    ) {
        if ($this->config->isActive() && !$this->config->canShowReviewTab()) {
            return '';
        }

        return $result;
    }
}
