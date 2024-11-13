<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Product;

use Lipscore\RatingsReviews\Model\Logger;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class Gtin implements OptionSourceInterface
{
    protected $eavConfig;

    protected $logger;

    public function __construct(
        Logger $logger,
        Config $eavConfig
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
            ->getEntityType(Product::ENTITY)
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
