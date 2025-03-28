<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends AbstractField
{
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->module->getModuleVersion();
    }

    public function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }
}
