<?php

namespace Lipscore\RatingsReviews\Plugin;

class ReviewRendererPlugin
{
    protected $moduleHelper;
    protected $logger;
    protected $lipscoreReviewRenderer;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Helper\Module $moduleHelper,
        \Lipscore\RatingsReviews\Block\Product\ReviewRenderer $lipscoreReviewRenderer
    ) {
        $this->moduleHelper           = $moduleHelper;
        $this->logger                 = $logger;
        $this->lipscoreReviewRenderer = $lipscoreReviewRenderer;
    }

    public function aroundGetReviewsSummaryHtml(
        \Magento\Review\Block\Product\ReviewRenderer $subject,
        callable $proceed,
        \Magento\Catalog\Model\Product $product,
        $templateType = \Magento\Catalog\Block\Product\ReviewRendererInterface::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        try {
            if ($this->moduleHelper->isLipscoreOutputEnabled()) {
                 return $this->lipscoreReviewRenderer->getReviewsSummaryHtml(
                     $product, $templateType, $displayIfNoReviews
                 );
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $proceed($product, $templateType, $displayIfNoReviews);
    }
}
