<?php

namespace Lipscore\RatingsReviews\Helper\Reminder;

use Magento\Bundle\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class ProductType
{
    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var Grouped
     */
    protected $grouped;

    /**
     * @var Type
     */
    protected $bundle;

    /**
     * @var array
     */
    protected $types = [];

    /**
     * ProductType constructor.
     *
     * @param Grouped $grouped
     * @param Configurable $configurable
     * @param Type $bundle
     */
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

    /**
     * Get the parent product id for a given child product id.
     *
     * @param int|string $childId
     * @return int|string|null
     */
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
