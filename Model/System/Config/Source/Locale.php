<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

class Locale implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'auto', 'label' => __('Auto')],
            ['value' => 'br',   'label' => __('Brazilian')],
            ['value' => 'cz',   'label' => __('Czech')],
            ['value' => 'dk',   'label' => __('Danish')],
            ['value' => 'nl',   'label' => __('Dutch')],
            ['value' => 'en',   'label' => __('English')],
            ['value' => 'et',   'label' => __('Estonian')],
            ['value' => 'fi',   'label' => __('Finnish')],
            ['value' => 'fr',   'label' => __('French')],
            ['value' => 'de',   'label' => __('German')],
            ['value' => 'it',   'label' => __('Italian')],
            ['value' => 'ja',   'label' => __('Japanese')],
            ['value' => 'lv',   'label' => __('Latvian')],
            ['value' => 'no',   'label' => __('Norwegian')],
            ['value' => 'pl',   'label' => __('Polish')],
            ['value' => 'br',   'label' => __('Portuguese (Brazil)')],
            ['value' => 'ru',   'label' => __('Russian')],
            ['value' => 'sk',   'label' => __('Slovak')],
            ['value' => 'es',   'label' => __('Spanish')],
            ['value' => 'se',   'label' => __('Swedish')],
        ];
    }
}
