<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field\Email;

use Lipscore\RatingsReviews\Block\System\Config\Form\Field\AbstractField;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Text extends AbstractField
{
    public function render(AbstractElement $element)
    {
        return '<p>The single most important feature to get ratings and reviews is to send existing customers Review
            Request Emails after the customer has received the product.<br/>
            Please choose which order status that triggers these emails (previews can be seen in your
            <a href="https://members.lipscore.com/">Lipscore Dashboard</a>)</p>';
    }
}
