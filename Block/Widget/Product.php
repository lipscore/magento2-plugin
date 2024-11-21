<?php

namespace Lipscore\RatingsReviews\Block\Widget;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class Product extends AbstractBlock
{
    protected $_template = 'widget/product/attributes.phtml';

    public function _beforeToHtml()
    {
        $this->setTemplate('widget/product/' . $this->getData('widget_template') . '.phtml');
        return parent::_beforeToHtml();
    }

    public function getDefaultDisplay()
    {
        return $this->getData('default_display') ?? 'none';
    }
}
