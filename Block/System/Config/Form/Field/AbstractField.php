<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Module;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;

class AbstractField extends Field
{
    protected $config;

    protected $module;

    public function __construct(
        Context $context,
        Config $config,
        Module $module,
        array $data = []
    ) {
        $this->config = $config;
        $this->module = $module;

        parent::__construct($context, $data);
    }
}
