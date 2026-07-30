<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Locale implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'auto',  'label' => __('Auto')],
            ['value' => 'cs',    'label' => __('Czech')],
            ['value' => 'da',    'label' => __('Danish')],
            ['value' => 'nl',    'label' => __('Dutch')],
            ['value' => 'en',    'label' => __('English')],
            ['value' => 'et',    'label' => __('Estonian')],
            ['value' => 'fi',    'label' => __('Finnish')],
            ['value' => 'fr',    'label' => __('French')],
            ['value' => 'de',    'label' => __('German')],
            ['value' => 'it',    'label' => __('Italian')],
            ['value' => 'ja',    'label' => __('Japanese')],
            ['value' => 'ko',    'label' => __('Korean')],
            ['value' => 'lv',    'label' => __('Latvian')],
            ['value' => 'no',    'label' => __('Norwegian')],
            ['value' => 'pl',    'label' => __('Polish')],
            ['value' => 'pt-BR', 'label' => __('Portuguese (Brazil)')],
            ['value' => 'pt-PT', 'label' => __('Portuguese (Portugal)')],
            ['value' => 'ru',    'label' => __('Russian')],
            ['value' => 'sk',    'label' => __('Slovak')],
            ['value' => 'es',    'label' => __('Spanish')],
            ['value' => 'sv',    'label' => __('Swedish')],
        ];
    }
}
