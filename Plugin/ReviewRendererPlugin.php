<?php

namespace Lipscore\RatingsReviews\Plugin;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Block\Product\ReviewRenderer;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Review\Block\Product\ReviewRenderer as Subject;


class ReviewRendererPlugin
{
    protected $config;

    protected $logger;

    protected $lipscoreReviewRenderer;

    public function __construct(
        Logger $logger,
        Config $config,
        ReviewRenderer $lipscoreReviewRenderer
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->lipscoreReviewRenderer = $lipscoreReviewRenderer;
    }

    public function aroundGetReviewsSummaryHtml(
        Subject $subject,
        callable $proceed,
        Product $product,
        $templateType = ReviewRendererInterface::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        try {
            if ($this->config->isLipscoreOutputEnabled()) {
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
