<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Fieldset\Dashboard;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Link extends Fieldset
{
    public function render(AbstractElement $element)
    {
        return <<<EOT
<div class="lipscore-dashboard-link">
    Advanced settings are available on <a href="https://members.lipscore.com/">your Lipscore.com dashboard</a>
</div>
EOT;
    }
}
