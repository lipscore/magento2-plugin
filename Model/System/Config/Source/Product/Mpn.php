<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Product;

use \Magento\Framework\Data\Collection;

class Mpn implements \Magento\Framework\Option\ArrayInterface
{
    protected $eavConfig;
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
        $this->logger    = $logger;
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
                    'value' => $attr->getAttributeCode(),
                    'label' => $attr->getStoreLabel()
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

        $collection = $this->eavConfig
            ->getEntityType(\Magento\Catalog\Model\Product::ENTITY)
            ->getAttributeCollection()
            ->addFieldToFilter('backend_type', ['in' => ['varchar', 'text', 'int']])
            ->addFieldToFilter('frontend_input', ['in' => ['text', 'textarea']])
            ->addOrder('frontend_label', Collection::SORT_ORDER_ASC);

        if ($collection->getSize() > 0) {
            return $collection->getItems();
        }

        return $attrs;
    }
}
