<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Module;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;

class AbstractField extends Field
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Module
     */
    protected $module;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Module $module
     * @param array $data
     */
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
