<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Block\Product\Review\Single;
use Magento\Framework\View\Layout;

class ReviewTab extends AbstractObserver
{
    /**
     * @var string
     */
    protected static $logFile = 'ls_review_tab_observer';

    /**
     * Add the Lipscore review block to the product view layout.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->checkIfEnabled()) {
            return;
        }

        $layout = $observer->getData('layout');
        if (!$layout) {
            return;
        }

        $layoutHandles = $layout->getUpdate()->getHandles();
        $properLayout  = in_array('catalog_product_view', $layoutHandles);

        if (!$properLayout) {
            return;
        }

        /** @var $layout Layout */
        if ($this->config->canShowReviewTab() && $this->checkIfBlockContentIsEmpty($layout, 'reviews.tab')) {
            $layout->addBlock(
                Single::class,
                'reviews.single',
                'content',
                'lipscore_reviews_single'
            );
        }
    }

    /**
     * Check whether the observer method is available to run.
     *
     * @return bool
     */
    protected function methodAvailable()
    {
        return $this->config->isActive();
    }
}
