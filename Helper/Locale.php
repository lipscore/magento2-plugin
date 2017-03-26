<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;

class Locale extends AbstractHelper
{
    protected static $availableLocales = [
        'en', 'it', 'no', 'es', 'br', 'ru', 'se', 'cz', 'nl', 'dk', 'ja', 'de', 'fi', 'fr'
    ];

    public function getLipscoreLocale()
    {
        $locale = null;
        try {
            $locale = $this->lipscoreConfig->locale();
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        if ($locale == 'auto') {
            $locale = null;
            try {
                $locale = $this->getFromStore();
            } catch (\Exception $e) {
                $this->logger->log($e);
            }
        }
        return $locale;
    }

    public function getStoreLocale()
    {
        $locale = '';
        try {
            $locale = $this->getLipscoreLocale();
            if (!$locale) {
                $localeCode = $this->lipscoreConfig->storeLocaleCode();
                list($locale, $region) = explode('_', $localeCode);
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $locale ? $locale : 'en';
    }

    protected function getFromStore()
    {
        $localeCode = $this->lipscoreConfig->storeLocaleCode();
        list($language, $region) = explode('_', $localeCode);

        $locale = $this->getAvailableLocale($language);
        if (is_null($locale)) {
            $locale = $this->getAvailableLocale($region);
        }
        return $locale;
    }

    protected function getAvailableLocale($locale)
    {
        $locale = strtolower($locale);
        return in_array($locale, self::$availableLocales) ? $locale : null;
    }
}
