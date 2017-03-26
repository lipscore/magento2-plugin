<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractWidget;

class Review extends AbstractWidget
{
    protected $_template = 'reviews/view.phtml';

    protected function _beforeToHtml()
    {
        try {
            $this->setTabTitle();
        } catch (\Exception $e) {
            $this->logger->log($e);
            $this->setTitle(__('Reviews'));
        }
        parent::_beforeToHtml();
        return $this;
    }

    protected function setTabTitle()
    {
        $title = $this->getLayout()
            ->createBlock('Lipscore\RatingsReviews\Block\Product\Review\Title')
            ->toHtml();
        $this->setTitle($title);
    }
}
