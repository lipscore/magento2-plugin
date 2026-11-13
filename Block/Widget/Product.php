<?php

namespace Lipscore\RatingsReviews\Block\Widget;

use Lipscore\RatingsReviews\Block\AbstractBlock;

class Product extends AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'widget/product/attributes.phtml';

    /**
     * Set the template based on the configured widget template before rendering.
     *
     * @return $this
     */
    public function _beforeToHtml()
    {
        $this->setTemplate('widget/product/' . $this->getData('widget_template') . '.phtml');
        return parent::_beforeToHtml();
    }

    /**
     * Get the default display value for the widget.
     *
     * @return string
     */
    public function getDefaultDisplay()
    {
        return $this->getData('default_display') ?? 'none';
    }
}
