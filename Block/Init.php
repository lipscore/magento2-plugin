<?php

namespace Lipscore\RatingsReviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Lipscore\RatingsReviews\Helper\Locale;
use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\View\Element\Template;

class Init extends Template
{
    protected $config;

    protected $localeHelper;

    public function __construct(
        Context $context,
        Config $config,
        Locale $localeHelper,
        array $data = []
    ) {
        $this->config = $config;
        $this->localeHelper = $localeHelper;

        parent::__construct($context, $data);
    }

    public function getApiKey()
    {
        return $this->config->getApiKey();
    }

    public function getAssetsUrl()
    {
        return $this->config->getAssetsUrl();
    }

    public function getIsLipscoreActive()
    {
        return $this->config->isLipscoreOutputEnabled() && $this->config->isLipscoreModuleEnabled();
    }

    protected function getLocale()
    {
        $locale = $this->localeHelper->getLipscoreLocale();

        return $locale ? $locale . '/' : '';
    }
}
