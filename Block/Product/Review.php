<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class Review extends AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'reviews/view.phtml';

    /**
     * Set the block title before rendering.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->setTitle(__('Reviews'));
        parent::_beforeToHtml();
        return $this;
    }
}
