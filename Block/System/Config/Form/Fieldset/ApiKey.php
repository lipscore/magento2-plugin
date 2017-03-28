<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Fieldset;

use Magento\Config\Block\System\Config\Form\Fieldset;

class ApiKey extends Fieldset
{
    protected function _isCollapseState($element)
    {
        return true;
    }
}
