<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;
use Lipscore\RatingsReviews\Block\Product\QA\Title;

class QA extends AbstractBlock
{
    protected $_template = 'qa/view.phtml';

    protected function _beforeToHtml()
    {
        try {
            $this->setTabTitle();
        } catch (\Exception $e) {
            $this->logger->log($e);
            $this->setTitle(__('Q&A'));
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
