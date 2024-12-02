<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Model\Product;

class ReviewRenderer extends AbstractBlock implements ReviewRendererInterface
{
    const SHORT_WIDGET = 'short';
    const LONG_WIDGET  = 'long';

    protected static $defaultWidgetType = self::SHORT_WIDGET;

    protected static $availableRatings = [
        self::LONG_WIDGET  => 'id="lipscore-rating"',
        self::SHORT_WIDGET => 'class="lipscore-rating-small"'
    ];

    protected $mainProductRatingDisplayed = false;

    protected $_template = 'ratings/view.phtml';

    /**
     * Get review summary html
     *
     * @param Product $product
     * @param string  $templateType
     * @param bool    $displayIfNoReviews
     *
     * @return string
     */
    public function getReviewsSummaryHtml(
        Product $product,
        $templateType = self::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        try {
            $this->initReviewSummary($product, $templateType);
        } catch (\Exception $e) {
            $this->logger->log($e);
            return '';
        }

        return $this->toHtml();
    }

    protected function initReviewSummary($product, $templateType)
    {
        $this->setProduct($product);

        $ratingType = self::defaultRatingType();
        try {
            $ratingType = $this->findRatingType($product, $templateType);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        $this->setRatingType($ratingType);
        $this->setIsShortType($templateType == self::SHORT_VIEW);
    }

    protected function findRatingType($product, $templateType)
    {
        $layoutHandles = $this->getLayout()->getUpdate()->getHandles();
        $isProductView = in_array('catalog_product_view', $layoutHandles);
        if ($isProductView && $this->isMainProductRating($product)) {
            $this->mainProductRatingDisplayed = true;
            return self::$availableRatings[self::LONG_WIDGET];
        }

        if (isset(self::$availableRatings[$templateType])) {
            return self::$availableRatings[$templateType];
        } else {
            return self::defaultRatingType();
        }
    }

    protected function isMainProductRating($product)
    {
        $mainProductRating = false;
        $mainProduct = $this->coreRegistry->registry('current_product');
        if (!$mainProduct) {
            return false;
        }

        if ($product->getId() == $mainProduct->getId() && !$this->mainProductRatingDisplayed) {
            $mainProductRating = true;
        }

        return $mainProductRating;
    }

    protected static function defaultRatingType()
    {
        return self::$availableRatings[static::$defaultWidgetType];
    }
}
