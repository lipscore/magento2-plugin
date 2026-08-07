<?php

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class QA extends AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'qa/view.phtml';

    /**
     * Render the block as HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        if (!$this->config->isActive() || !$this->config->canShowQa()) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * Set the block title before rendering.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->setTitle(__('Q&A'));
        parent::_beforeToHtml();
        return $this;
    }
}
