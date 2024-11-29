<?php

namespace Lipscore\RatingsReviews\Helper;

class Locale extends AbstractHelper
{
    public const AVAILABLE_LOCALES = [
        'br', 'cz', 'dk', 'nl', 'en', 'et', 'fi', 'fr', 'de', 'it', 'ja', 'lv', 'no', 'pl', 'br', 'ru', 'sk', 'es', 'se'
    ];

    public function getLipscoreLocale($storeId = null)
    {
        $locale = null;
        try {
            $locale = $this->config->getLocale($storeId);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        if ($locale === 'auto') {
            $locale = null;
            try {
                $locale = $this->getLocaleFromStore($storeId);
            } catch (\Exception $e) {
                $this->logger->log($e);
            }
        }
        return $locale;
    }

    protected function getLocaleFromStore($storeId = null)
    {
        $localeCode = $this->config->getStoreLocale($storeId);
        list($language, $region) = explode('_', $localeCode);

        $locale = $this->getAvailableLocale($language);
        if ($locale === null) {
            $locale = $this->getAvailableLocale($region);
        }

        return $locale;
    }

    protected function getAvailableLocale($locale)
    {
        $locale = strtolower($locale);

        return in_array($locale, self::AVAILABLE_LOCALES) ? $locale : null;
    }
}
