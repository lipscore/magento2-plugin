<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Block\Product\Review\Single;

class ReviewTab extends AbstractObserver
{
    protected static $logFile = 'ls_review_tab_observer';

    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getData('layout');

        if (!$layout) {
            return;
        }

        if (!$this->config->canShowReviewTab() && $layout->hasElement('lipscore_reviews.tab')) {
            $layout->unsetElement('lipscore_reviews.tab');
            return;
        }

        $layoutHandles = $layout->getUpdate()->getHandles();
        $properLayout  = in_array('catalog_product_view', $layoutHandles);

        if (!$properLayout) {
            return;
        }

        if ($this->config->canShowReviewTab() && !$layout->hasElement('lipscore_reviews.tab')) {
            $layout->addBlock(
                Single::class,
                'reviews.single',
                'content',
                'lipscore_reviews_single'
            );
        }
    }

    protected function methodAvailable()
    {
        return $this->config->isActive();
    }
}
