<?php

namespace Lipscore\RatingsReviews\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\AbstractElement;

Class TextareaField extends Template
{
    protected $elementFactory;

    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;

        parent::__construct($context, $data);
    }

    public function prepareElementHtml(AbstractElement $element)
    {
        $input = $this->elementFactory->create("textarea", ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }

        $element->setData('after_element_html', $input->getElementHtml());

        return $element;
    }
}
