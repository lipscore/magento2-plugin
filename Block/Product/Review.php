<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class Review extends AbstractBlock
{
    protected $_template = 'reviews/view.phtml';

    protected function _beforeToHtml()
    {
        $this->setTitle(__('Reviews'));
        parent::_beforeToHtml();
        return $this;
    }
}
