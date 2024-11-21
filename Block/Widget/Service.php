<?php

namespace Lipscore\RatingsReviews\Block\Widget;

use Lipscore\RatingsReviews\Block\AbstractBlock;
use Lipscore\RatingsReviews\Block\Product\Review\Title;

class Service extends AbstractBlock
{
    public function _beforeToHtml()
    {
        $this->getData('widget_template');
        $this->setTemplate('widget/service/' . $this->getData('widget_template') . '.phtml');
        return parent::_beforeToHtml();
    }

    public function getProductAttributes()
    {
        return [
            'product-id' => 'service_review',
            'product-name' => 'Service reviews',
            'product-url' => '',
        ];
    }

    public function getDefaultDisplay()
    {
        return $this->getData('default_display') ?? 'none';
    }

    public function getSharedAttributes()
    {
        return [
            'height',
            'width'
        ];
    }

    public function getBadgeOptions()
    {
        return [
            $this->getData('widget_template') . '_option_noborder',
            $this->getData('widget_template') . '_option_noseparator'
        ];
    }

    public function getAttributeValue($key)
    {
        return $this->getData($this->getData('widget_template') . '_' . $key);
    }

    public function getAttributeKey($key, $prefix = 'data-ls-widget-')
    {
        return $prefix . $key;
    }

    public function getSharedWidgetAttributes()
    {
        $attributes = [];
        foreach ($this->getSharedAttributes() as $attribute) {
            $value = $this->getAttributeValue($attribute);
            if ($value) {
                $attributes[] = sprintf('%s="%s"', $this->getAttributeKey($attribute), $value);
            }
        }

        return $attributes ? implode(' ', $attributes) : null;
    }

    public function getBadgeWidgetOptions()
    {
        $options = [];
        foreach ($this->getBadgeOptions() as $attribute) {
            $value = $this->getAttributeValue($attribute);
            if ($value) {
                $options[$attribute] = $value;
            }
        }

        return $options ? implode(' ', $options) : null;
    }

    public function getProductWidgetAttributes()
    {
        $attributes = [];
        foreach ($this->getProductAttributes() as $attribute => $value) {
            if (!$value) {
                $value = $this->getAttributeValue($attribute);
            }
            if ($value) {
                $attributes[] = sprintf('%s="%s"', $this->getAttributeKey($attribute, 'data-ls-'), $value);
            }
        }

        return $attributes ? implode(' ', $attributes) : null;
    }
}
