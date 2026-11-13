<?php

namespace Lipscore\RatingsReviews\Plugin;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\ProductData;
use Magento\Review\Block\Product\Review as MagentoReviewBlock;

class ReviewTabPlugin
{
    /**
     * @var ProductData
     */
    protected $productData;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Initialize plugin dependencies.
     *
     * @param ProductData $productData
     * @param Config $config
     */
    public function __construct(
        ProductData $productData,
        Config $config
    ) {
        $this->productData = $productData;
        $this->config = $config;
    }

    /**
     * Replace the review tab product attributes with Lipscore data when enabled.
     *
     * @param MagentoReviewBlock $subject
     * @param mixed $result
     * @param string $key
     * @return mixed
     */
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

    /**
     * Suppress the Magento review tab HTML when the Lipscore review tab is shown instead.
     *
     * @param MagentoReviewBlock $subject
     * @param string $result
     * @return string
     */
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
