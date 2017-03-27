<?php

namespace Lipscore\RatingsReviews\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Lipscore\RatingsReviews\Model\Config\AdminFactory;

class AbstractField extends Field
{
    protected $lipscoreConfig;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lipscore\RatingsReviews\Model\Config\AdminFactory $adminConfigFactory,
        array $data = []
    ) {
        // order is important, should be before config initialization
        parent::__construct($context, $data);

        $this->lipscoreConfig = $adminConfigFactory->create(
            [
                'websiteId' => $this->getRequest()->getParam('website', ''),
                'storeId'   => $this->getRequest()->getParam('store', '')
            ]
        );
    }
}
