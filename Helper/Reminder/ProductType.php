<?php

namespace Lipscore\RatingsReviews\Helper\Reminder;

use Magento\Bundle\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class ProductType
{
    protected $configurable;

    protected $grouped;

    protected $bundle;

    protected $types = [];

    public function __construct(
        Grouped $grouped,
        Configurable $configurable,
        Type $bundle
    ) {
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
