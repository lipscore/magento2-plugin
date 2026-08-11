<?php

namespace Lipscore\RatingsReviews\Setup\Patch\Data;

use Lipscore\RatingsReviews\Helper\Product as ProductHelper;
use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterfaceFactory;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddProductAttributes implements DataPatchInterface
{
    protected $moduleDataSetup;
    protected $eavSetupFactory;
    protected $attributeSetManagement;
    protected $attributeGroupFactory;
    protected $attributeGroupRepository;
    protected $attributeSetFactory;
    protected $product;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Product $product,
        EavSetupFactory $eavSetupFactory,
        AttributeSetInterfaceFactory $attributeSetInterfaceFactory,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeGroupRepositoryInterface $attributeGroupRepository
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->product = $product;
        $this->attributeSetFactory = $attributeSetInterfaceFactory;
        $this->attributeSetManagement = $attributeSetManagement;
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->attributeGroupRepository = $attributeGroupRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->createAttributeGroup('Lipscore Ratings & Reviews');

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            ProductHelper::MAGENTO_PRODUCT_ATTRIBUTE_RATING,
            [
                'type' => 'decimal',
                'label' => 'Lipscore Rating',
                'input' => 'text',
                'source' => '',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '100',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'unique' => false,
                'group' => 'Lipscore Ratings & Reviews',
                'used_in_product_listing' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => ''
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            ProductHelper::MAGENTO_PRODUCT_ATTRIBUTE_VOTE_COUNT,
            [
                'type' => 'int',
                'label' => 'Lipscore Votes Count',
                'input' => 'text',
                'source' => '',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '150',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'unique' => false,
                'group' => 'Lipscore Ratings & Reviews',
                'used_in_product_listing' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => ''
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            ProductHelper::MAGENTO_PRODUCT_ATTRIBUTE_REVIEW_COUNT,
            [
                'type' => 'int',
                'label' => 'Lipscore Reviews Count',
                'input' => 'text',
                'source' => '',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '200',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'unique' => false,
                'group' => 'Lipscore Ratings & Reviews',
                'used_in_product_listing' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => ''
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    protected function createAttributeGroup($attributeGroupName)
    {
        $attributeSetId = $this->product->getDefaultAttributeSetId();

        $attributeGroup = $this->attributeGroupFactory->create();
        $attributeGroup->setAttributeSetId($attributeSetId);
        $attributeGroup->setAttributeGroupName($attributeGroupName);

        $this->attributeGroupRepository->save($attributeGroup);
    }
}
