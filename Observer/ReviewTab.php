<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Block\Product\Review\Single;
use Magento\Framework\View\Layout;

class ReviewTab extends AbstractObserver
{
    protected static $logFile = 'ls_review_tab_observer';

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

    protected function methodAvailable()
    {
        return $this->config->isActive();
    }
}
