<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;

class LipscoreWidget implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get the list of available options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->getOptionIdArray();
        }

        return $this->options;
    }

    /**
     * Get widget instance options keyed by instance id.
     *
     * @return array
     */
    public function getOptionIdArray()
    {
        $options = $this->collectionFactory->create()
            ->addFieldToFilter('instance_type', ['like' => '%Lipscore%']);

        $array = [];
        $array[''] = __('Not selected');
        foreach ($options as $option) {
            $array[$option->getId()] = $option->getTitle();
        }

        return $array;
    }
}
