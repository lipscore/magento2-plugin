<?php

namespace Lipscore\RatingsReviews\Model\Action;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Model\Api\RequestFactory;
use Lipscore\RatingsReviews\Model\Config\AdminFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductActionResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollectionResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ImportRatings
{
    const LIPSCORE_API_PRODUCT_ID_PARAM = 'internal_id';

    protected $dataHelper;
    protected $config;
    protected $senderFactory;
    protected $productActionResource;
    protected $collectionFactory;
    protected $api;

    public function __construct(
        ProductActionResource $productActionResource,
        CollectionFactory $collectionFactory,
        AdminFactory $adminConfigFactory,
        RequestFactory $senderFactory
    ) {
        $this->config = $adminConfigFactory->create(
            [
                'storeId'   => 0,
                'websiteId' => null
            ]
        );

        $this->collectionFactory = $collectionFactory;
        $this->productActionResource = $productActionResource;
        $this->senderFactory = $senderFactory;
    }

    protected function getApi()
    {
        if (!$this->api) {
            $this->api = $this->senderFactory->create(
                [
                    'config' => $this->config,
                    'path'   => 'products',
                    'params' => [
                        'timeout' => $this->config->reminderTimeout(),
                        'requestType' => 'GET',
                        'additionalQueryParams' => '&fields=rating,votes,review_count'
                    ],
                ]
            );
        }

        return $this->api;
    }

    public function process()
    {
        $collection = $this->getProductCollection();
        $pages = $collection->getLastPageNumber();
        for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
            $collection->setCurPage($pageNum);
            $productIds = $collection->getColumnValues('entity_id');

            $apiProducts = $this->getApiProducts($productIds);
            $this->updateProducts($apiProducts);

            $collection->clear();
        }
    }

    public function getProductCollection()
    {
        /** @var ProductCollectionResource $collection  */
        $collection = $this->collectionFactory->create();
        $collection->setPageSize(80);

        return $collection;
    }

    public function getApiProducts($products)
    {
        $apiData = $this->getApi()->send([self::LIPSCORE_API_PRODUCT_ID_PARAM => $products]);

        return $this->parseApiResponse($apiData);
    }

    public function parseApiResponse($response)
    {
        if (!$response) {
            return [];
        }

        $parsedData = [];
        foreach ($response as $product) {
            $parsedData[$product[self::LIPSCORE_API_PRODUCT_ID_PARAM]] = [
                Product::MAGENTO_PRODUCT_ATTRIBUTE_VOTE_COUNT => $product['votes'] ?? 0,
                Product::MAGENTO_PRODUCT_ATTRIBUTE_RATING => (float) $product['rating'] ?? 0,
                Product::MAGENTO_PRODUCT_ATTRIBUTE_REVIEW_COUNT => $product['review_count'] ?? 0,
            ];
        }
        return $parsedData;
    }

    public function updateProducts($products)
    {
        if (!$products) {
            return;
        }

        foreach ($products as $productId => $attributes) {
            $this->productActionResource->updateAttributes([$productId], $attributes, 0);
        }
    }
}
