<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Lipscore\RatingsReviews\Block\System\Config\Form\Field\AbstractField;

class ApiKey extends AbstractField
{
    protected static $commentText = <<<EOT
<strong style="color:red">Warning!</strong>&nbsp;Your Lipscore installation is set up using a
Demo Account. Please sign up with your own account on <a href="http://lipscore.com/" target="_blank">
www.lipscore.com</a> to get access to all available features.
EOT;

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $isDemoKey = false;
        try {
            $isDemoKey = $this->lipscoreConfig->isDemoKey();
        } catch (\Exception $e) {
            return parent::render($element);
        }
        $comment = $isDemoKey ? $this->commentHtml() : '';
        return parent::_getElementHtml($element) . $comment;
    }

    protected function commentHtml()
    {
        return '<p class="note"><span>' . static::$commentText . '</span></p>';
    }
}
