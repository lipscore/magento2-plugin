<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field\Email;

use Lipscore\RatingsReviews\Block\System\Config\Form\Field\AbstractField;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Text extends AbstractField
{
    /**
     * Render the invitations field description.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return '<p>The single most important feature to get ratings and reviews is to invite existing customers to
            review their purchase after they have received the product.<br/>
            Choose the order status that schedules the invitations in Lipscore. Lipscore sends the invitation emails
            later (previews can be seen in your <a href="https://members.lipscore.com/">Lipscore Dashboard</a>).</p>';
    }
}
