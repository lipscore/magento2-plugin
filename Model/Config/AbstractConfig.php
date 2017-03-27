<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Lipscore\RatingsReviews\Model\Config\AccessorFactory;

abstract class AbstractConfig
{
    const REMINDER_TIMEOUT = 5;

    public $storeId;

    protected $websiteId;
    protected $accessor;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Config\AccessorFactory $configAccessorFactory
    ) {
        $this->accessor = $configAccessorFactory->create(
            [
                'websiteId' => $this->websiteId,
                'storeId'   => $this->storeId
            ]
        );
    }

    public function apiKey()
    {
        return $this->accessor->get('api_key', 'apiKey');
    }

    public function demoApiKey()
    {
        return $this->accessor->get('demo_api_key', 'apiKey');
    }

    public function locale()
    {
        return $this->accessor->get('locale', 'locale');
    }

    public function storeLocaleCode()
    {
        return $this->accessor->get('locale/code', 'storeGeneral');
    }

    public function brandAttr()
    {
        return $this->accessor->get('attr', 'brand');
    }

    public function lastTrackedVersion()
    {
        return $this->accessor->get('last_tracked_version', 'tracking');
    }

    public function pluginInstallationId()
    {
        return $this->accessor->get('plugin_installation_id', 'tracking');
    }

    public function reminderStatus()
    {
        return $this->accessor->get('order_status', 'emails');
    }

    public function reminderTimeout()
    {
        $timeout = getenv('REMINDER_TIMEOUT');
        return $timeout ? $timeout : static::REMINDER_TIMEOUT;
    }

    public function priceRuleId()
    {
        return $this->accessor->get('price_rule_id', 'coupons');
    }

    public function isModuleActive()
    {
        return $this->accessor->get('active', 'module');
    }

    public function isDemoKey()
    {
        $currentKey = $this->apiKey();
        $demokey    = $this->demoApiKey();
        return $currentKey == $demokey;
    }

    public function isValidApiKey()
    {
        return $this->apiKey() && !$this->isDemoKey();
    }

    public function setLastTrackedVersion($value)
    {
        return $this->accessor->set('last_tracked_version', 'tracking', $value);
    }

    public function setPluginInstallationId($value)
    {
        return $this->accessor->set('plugin_installation_id', 'tracking', $value);
    }
}
