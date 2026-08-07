<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Fieldset;

use Magento\Config\Block\System\Config\Form\Fieldset;

class ApiKey extends Fieldset
{
    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        return true;
    }
}
