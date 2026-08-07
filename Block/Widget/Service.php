<?php

namespace Lipscore\RatingsReviews\Block\Widget;

use Lipscore\RatingsReviews\Block\AbstractBlock;
use Lipscore\RatingsReviews\Block\Product\Review\Title;

class Service extends AbstractBlock
{
    /**
     * Set the template based on the configured widget template before rendering.
     *
     * @return $this
     */
    public function _beforeToHtml()
    {
        $this->getData('widget_template');
        $this->setTemplate('widget/service/' . $this->getData('widget_template') . '.phtml');
        return parent::_beforeToHtml();
    }

    /**
     * Get the product attributes used for the service review widget.
     *
     * @return array
     */
    public function getProductAttributes()
    {
        return [
            'product-id' => 'service_review',
            'product-name' => 'Service reviews',
            'product-url' => '',
        ];
    }

    /**
     * Get the default display value for the widget.
     *
     * @return string
     */
    public function getDefaultDisplay()
    {
        return $this->getData('default_display') ?? 'none';
    }

    /**
     * Get the list of attributes shared across widget types.
     *
     * @return array
     */
    public function getSharedAttributes()
    {
        return [
            'height',
            'width'
        ];
    }

    /**
     * Get the list of badge option attributes.
     *
     * @return array
     */
    public function getBadgeOptions()
    {
        return [
            'option_noborder',
            'option_noseparator'
        ];
    }

    /**
     * Get the value of the given widget attribute for the current widget template.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        return $this->getData($this->getData('widget_template') . '_' . $key);
    }

    /**
     * Build the HTML data attribute key for the given attribute.
     *
     * @param string $key
     * @param string $prefix
     * @return string
     */
    public function getAttributeKey($key, $prefix = 'data-ls-widget-')
    {
        return $prefix . $key;
    }

    /**
     * Build the HTML attribute string for the shared widget attributes.
     *
     * @return string|null
     */
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

    /**
     * Build the badge widget options string.
     *
     * @return string|null
     */
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

    /**
     * Build the HTML attribute string for the product widget attributes.
     *
     * @return string|null
     */
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
