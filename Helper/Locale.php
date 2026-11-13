<?php

namespace Lipscore\RatingsReviews\Helper;

class Locale extends AbstractHelper
{
    public const AVAILABLE_LOCALES = [
        'cs', 'da', 'nl', 'en', 'et', 'fi', 'fr', 'de', 'it', 'ja', 'ko', 'lv', 'no', 'pl',
        'pt-BR', 'pt-PT', 'ru', 'sk', 'es', 'sv'
    ];

    /**
     * Get the Lipscore locale code for the given store.
     *
     * @param int|null $storeId
     * @return string|null
     */
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

    /**
     * Resolve the Lipscore locale from the store's configured locale.
     *
     * @param int|null $storeId
     * @return string|null
     */
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

    /**
     * Find the matching available locale, case-insensitively.
     *
     * @param string $locale
     * @return string|null
     */
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
