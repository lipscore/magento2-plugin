<?php

namespace Lipscore\RatingsReviews\Helper;

class Locale extends AbstractHelper
{
    public const AVAILABLE_LOCALES = [
        'cs', 'da', 'nl', 'en', 'et', 'fi', 'fr', 'de', 'it', 'ja', 'ko', 'lv', 'no', 'pl', 'pt-BR', 'pt-PT', 'ru', 'sk', 'es', 'sv'
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

        $locale = $this->getAvailableLocale($language . '-' . $region);
        if ($locale === null) {
            $locale = $this->getAvailableLocale($language);
        }
        if ($locale === null) {
            $locale = $this->getAvailableLocale($region);
        }

        return $locale;
    }

    protected function getAvailableLocale($locale)
    {
        foreach (self::AVAILABLE_LOCALES as $availableLocale) {
            if (strcasecmp($availableLocale, $locale) === 0) {
                return $availableLocale;
            }
        }

        return null;
    }
}
