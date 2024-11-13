<?php

namespace Lipscore\RatingsReviews\Observer;

class ReviewTab extends AbstractObserver
{
    protected static $logFile = 'ls_review_tab_observer';

    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getData('layout');

        if (!$layout) {
            return;
        }

        $layoutHandles = $layout->getUpdate()->getHandles();
        $properLayout  = in_array('catalog_product_view', $layoutHandles);

        if (!$properLayout) {
            return;
        }

        if (!$layout->hasElement('lipscore_reviews.tab')) {
            $layout->addBlock(
                \Lipscore\RatingsReviews\Block\Product\Review\Single::class,
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
