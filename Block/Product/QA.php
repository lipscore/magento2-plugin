<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class QA extends AbstractBlock
{
    protected $_template = 'qa/view.phtml';

    public function toHtml()
    {
        if (!$this->config->isActive() || !$this->config->canShowQa()) {
            return '';
        }

        return parent::toHtml();
    }

    protected function _beforeToHtml()
    {
        $this->setTitle(__('Q&A'));
        parent::_beforeToHtml();
        return $this;
    }
}
