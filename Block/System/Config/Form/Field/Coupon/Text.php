<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field\Coupon;

use Lipscore\RatingsReviews\Block\System\Config\Form\Field\AbstractField;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Text extends AbstractField
{
    public function render(AbstractElement $element)
    {
        return <<<EOT
<p>Coupons are a great way to give customers an incentive to write a review. Create a Cart Price Rule in the "Marketing"
section and select it here. Its coupon code will be emailed to your customers after their review has been submitted.</p>
EOT;
    }
}
