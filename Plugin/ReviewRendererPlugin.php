<?php

namespace Lipscore\RatingsReviews\Plugin;

use Lipscore\RatingsReviews\Block\Product\ReviewRenderer;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Model\Product;
use Magento\Review\Block\Product\ReviewRenderer as Subject;

class ReviewRendererPlugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ReviewRenderer
     */
    protected $lipscoreReviewRenderer;

    /**
     * Initialize plugin dependencies.
     *
     * @param Logger $logger
     * @param Config $config
     * @param ReviewRenderer $lipscoreReviewRenderer
     */
    public function __construct(
        Logger $logger,
        Config $config,
        ReviewRenderer $lipscoreReviewRenderer
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->lipscoreReviewRenderer = $lipscoreReviewRenderer;
    }

    /**
     * Replace the reviews summary HTML with the Lipscore renderer's output when enabled.
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param Product $product
     * @param string $templateType
     * @param bool $displayIfNoReviews
     * @return string
     */
    public function aroundGetReviewsSummaryHtml(
        Subject $subject,
        callable $proceed,
        Product $product,
        $templateType = ReviewRendererInterface::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        try {
            if ($this->config->isLipscoreOutputEnabled() && $this->config->canShowRatings()) {
                return $this->lipscoreReviewRenderer->getReviewsSummaryHtml(
                    $product,
                    $templateType,
                    $displayIfNoReviews
                );
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $proceed($product, $templateType, $displayIfNoReviews);
    }
}
