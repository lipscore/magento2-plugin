<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;
use Lipscore\RatingsReviews\Block\Product\Review\Title;

class Review extends AbstractBlock
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
            ->createBlock(Title::class)
            ->toHtml();
        $this->setTitle($title);
    }
}
