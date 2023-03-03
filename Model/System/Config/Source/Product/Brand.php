<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Product;

use Magento\Framework\Data\Collection;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Type;
use Lipscore\RatingsReviews\Model\Logger;

class Brand implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeFactory;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     * @param AttributeCollectionFactory $attributeFactory
     * @param Type $type
     */
    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeFactory,
        \Magento\Eav\Model\Entity\Type $type
    ) {
        $this->logger           = $logger;
        $this->attributeFactory = $attributeFactory;
        $this->type             = $type;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '', 'label' => __('--Please Select--')]
        ];

        try {
            $attrs = $this->findAttrs();
            foreach ($attrs as $attr) {
                $options[] = [
                    'value' => $attr['code'],
                    'label' => $attr['label']
                ];
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $options;
    }

    protected function findAttrs()
    {
        $attrs = [];

        $entityTypeId = $this->type->loadByCode(Product::ENTITY)->getId();
        $collection = $this->attributeFactory->create()
            ->removeAllFieldsFromSelect()
            ->addFieldToSelect('attribute_code', 'code')
            ->addFieldToSelect('frontend_label', 'label')
            ->addFieldToFilter('entity_type_id', ['eq' => $entityTypeId])
            ->addFieldToFilter('backend_type', ['in' => ['varchar', 'text', 'int']])
            ->addFieldToFilter('frontend_input', ['in' => ['text', 'textarea', 'select']])
            ->setOrder('frontend_label', Collection::SORT_ORDER_ASC);

        if ($collection->getSize() > 0) {
            return $collection->getItems();
        }

        return $attrs;
    }
}
