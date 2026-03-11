<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Api;

use GuzzleHttp\ClientInterface;
use Lipscore\RatingsReviews\Model\Service\JsonLd\ProductResolver;

class ProductRequest
{
    private const PRODUCTS_PER_PAGE = 30;
    private const PRODUCT_FIELDS    = 'rating,votes,review_count,reviews';

    protected $client;
    protected $productResolver;

    public function __construct(
        ClientInterface  $client,
        ProductResolver  $productResolver
    ) {
        $this->client = $client;
        $this->productResolver = $productResolver;
    }

    public function fetchProductsBatch(array $magentoProductIds, $context): array
    {
        $map = $this->productResolver->getMagentoToLipscoreMap($magentoProductIds);

        if (!$map) {
            return [];
        }

        $apiData = $this->fetchFromLipscoreBatch(array_values($map), $context);

        return $this->mapApiDataToMagento($apiData, $map);
    }

    private function fetchFromLipscoreBatch(array $internalIds, $context): array
    {
        $url = rtrim($context['api_url'], '/') . '/products?' . http_build_query([
                'api_key'  => $context['api_key'],
                'per_page' => self::PRODUCTS_PER_PAGE,
                'fields'   => self::PRODUCT_FIELDS,
            ]) . '&' . implode('&', array_map(
                static fn(string $id) => 'internal_id[]=' . urlencode($id),
                $internalIds
            ));

        $response = $this->client->get($url, [
            'headers' => [
                'X-Authorization' => $context['api_secret'],
                'Accept'          => 'application/json',
            ],
            'timeout' => 0.5,
        ]);

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        return json_decode((string)$response->getBody(), true) ?: [];
    }

    private function mapApiDataToMagento(array $apiData, array $magentoToLipscore): array
    {
        $lipscoreToMagento = array_flip($magentoToLipscore);

        $result = [];
        foreach ($apiData as $product) {
            $internalId = $product['internal_id'] ?? null;

            if ($internalId === null || !isset($lipscoreToMagento[$internalId])) {
                continue;
            }

            $product['magento_product_id'] = (int)$lipscoreToMagento[$internalId];
            $result[]                      = $product;
        }

        return $result;
    }
}
