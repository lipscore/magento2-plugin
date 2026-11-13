<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends AbstractField
{
    /**
     * Return the module version as the element html.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->module->getModuleVersion();
    }

    /**
     * Render the scope label.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }
}
