<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;

class AbstractField extends Field
{
    protected $config;

    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;

        parent::__construct($context, $data);
    }
}
