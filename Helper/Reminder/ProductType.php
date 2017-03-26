<?php

namespace Lipscore\RatingsReviews\Helper\Reminder;

class ProductType
{
    protected $configurable;
    protected $grouped;
    protected $bundle;

    protected $types = [];

    public function __construct(
        \Magento\GroupedProduct\Model\Product\Type\Grouped $grouped,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Bundle\Model\Product\Type $bundle
    ){
        $this->configurable = $configurable;
        $this->grouped      = $grouped;
        $this->bundle       = $bundle;

        $this->types = [$this->configurable, $this->grouped, $this->bundle];
    }

    public function getParentId($childId)
    {
        $parentId = null;

        foreach ($this->types as $key => $type) {
            $parentIds = $type->getParentIdsByChild($childId);
            if (!empty($parentIds[0])) {
                $parentId = $parentIds[0];
                break;
            }
        }

        return $parentId;
    }
}
