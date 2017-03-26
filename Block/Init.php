<?php

namespace Lipscore\RatingsReviews\Block;

class Init extends \Magento\Framework\View\Element\Template
{
    protected $env;
    protected $lipscoreConfig;
    protected $moduleHelper;
    protected $localeHelper;
    protected $logger;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Env $env,
        \Lipscore\RatingsReviews\Model\Config\Front $config,
        \Lipscore\RatingsReviews\Helper\Locale $localeHelper,
        \Lipscore\RatingsReviews\Helper\Module $moduleHelper,
        array $data = []
    )
    {
        $this->env            = $env;
        $this->lipscoreConfig = $config;
        $this->localeHelper   = $localeHelper;
        $this->moduleHelper   = $moduleHelper;
        $this->logger         = $logger;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        try {
            $this->setApiKey($this->lipscoreConfig->apiKey());
            $this->setAssetsUrl($this->env->assetsUrl());
            $this->setLocale($this->getLocale());
            $this->setIsLipscoreActive($this->moduleHelper->isLipscoreActive());
        } catch (\Exception $e) {
           $this->logger->log($e);
        }

        return parent::_beforeToHtml();
    }

    protected function getLocale()
    {
        $locale = $this->localeHelper->getLipscoreLocale();
        return $locale ? $locale . '/' : '';
    }
}
