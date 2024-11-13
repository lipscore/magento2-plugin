<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;

class LipscoreWidget implements \Magento\Framework\Option\ArrayInterface
{
    protected $options;

    protected $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->getOptionIdArray();
        }

        return $this->options;
    }

    /**
     * @return array
     */
    public function getOptionIdArray()
    {
        $options = $this->collectionFactory->create()
            ->addFieldToFilter('instance_type', array('like' => '%Lipscore%'));

        $array = [];
        $array[''] = __('Not selected');
        foreach ($options as $option) {
            $array[$option->getId()] = $option->getTitle();
        }

        return $array;
    }
}
