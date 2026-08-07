<?php

namespace Lipscore\RatingsReviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Lipscore\RatingsReviews\Helper\Locale;
use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\View\Element\Template;

class Init extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Locale
     */
    protected $localeHelper;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Locale $localeHelper
     * @param array $data
     */
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

    /**
     * Get the Lipscore API key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->config->getApiKey();
    }

    /**
     * Get the Lipscore assets URL.
     *
     * @return string
     */
    public function getAssetsUrl()
    {
        return $this->config->getAssetsUrl();
    }

    /**
     * Check whether Lipscore is active.
     *
     * @return bool
     */
    public function getIsLipscoreActive()
    {
        return $this->config->isLipscoreOutputEnabled() && $this->config->isLipscoreModuleEnabled();
    }

    /**
     * Get the current locale code.
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = $this->localeHelper->getLipscoreLocale();

        return $locale ? $locale . '/' : '';
    }
}
