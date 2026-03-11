<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Service\JsonLd;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Model\Logger\Logger;
use Magento\Catalog\Api\Data\ProductInterface;

class JsonLdBuilder
{
    private const FIELD_MAPPING = [
        'url'         => 'url',
        'description' => 'description',
        'image'       => 'image_url',
        'sku'         => 'sku',
        'mpn'         => 'mpn',
    ];

    private const RATING_BEST         = 5;
    private const RATING_WORST        = 1;
    private const MAX_DESC_LENGTH     = 200;
    private const VALID_GTIN_LENGTHS  = [8, 12, 13, 14];

    protected $logger;

    protected $helper;

    public function __construct(
        Logger  $logger,
        Product $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function build(ProductInterface $product, array $ratings): array
    {
        $stale   = (bool) ($ratings['stale'] ?? false);
        $data    = $this->getProductData($product);

        $schema = $this->buildProductData($data, $ratings);
        if ($stale) {
            $schema['_stale'] = true;
        }

        return $schema;
    }

    public function buildReviews(ProductInterface $product, array $ratings): array
    {
        $reviews = $this->normalizeReviews($ratings);

        if (empty($reviews)) {
            return [];
        }

        $data   = $this->getProductData($product);
        $url    = $data['url'] ?? null;
        $schema = $this->buildProductBase($data);

        $schema['review'] = $this->mapReviews($reviews, $url);

        return $schema;
    }

    private function buildProductBase(array $data): array
    {
        $schema = [
            '@context' => 'http://schema.org',
            '@type'    => 'Product',
        ];

        if (!empty($data['url'])) {
            $schema['@id'] = $data['url'] . '#this';
        }

        if (!empty($data['name'])) {
            $schema['name'] = $data['name'];
        }

        return $schema;
    }

    private function buildProductData(array $data, array $ratings): array
    {
        $schema = $this->buildProductBase($data);

        // simple scalar fields
        foreach (self::FIELD_MAPPING as $schemaField => $helperKey) {
            if (!empty($data[$helperKey])) {
                $value = $data[$helperKey];

                if ($schemaField === 'description') {
                    $value = $this->truncateDescription($value);
                }

                $schema[$schemaField] = $value;
            }
        }

        // brand as typed object
        if (!empty($data['brand'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name'  => $data['brand'],
            ];
        }

        // category
        if (!empty($data['category'])) {
            $schema['category'] = $data['category'];
        }

        // aggregateRating
        $aggregateRating = $this->buildAggregateRating($ratings);
        if ($aggregateRating !== null) {
            $schema['aggregateRating'] = $aggregateRating;
        }

        // offers
        if (!empty($data['price']) && !empty($data['currency'])) {
            $schema['offers'] = $this->buildOffer($data);
        }

        $sku        = $this->firstValue($data['sku'] ?? '');
        $mpn        = $this->firstValue($data['mpn'] ?? '');
        $validGtins = $this->filterGtins($data['gtin'] ?? []);

        $this->addGtins($schema, $validGtins);
        $this->addProductId($schema, $sku, $mpn, $validGtins);

        return $schema;
    }

    private function buildAggregateRating(array $ratings): ?array
    {
        $ratingCount = (int) ($ratings['votes_count'] ?? $ratings['review_count'] ?? 0);

        if ($ratingCount <= 0) {
            return null;
        }

        $data = [
            '@type'       => 'AggregateRating',
            'ratingValue' => $ratings['rating_value'] ?? null,
            'bestRating'  => self::RATING_BEST,
            'worstRating' => self::RATING_WORST,
        ];

        $reviewCount = (int) ($ratings['review_count'] ?? 0);
        if ($reviewCount > 0) {
            $data['reviewCount'] = $reviewCount;
        }
        if ($ratingCount > 0) {
            $data['ratingCount'] = $ratingCount;
        }

        return $data;
    }

    private function buildOffer(array $data): array
    {
        $offer = [
            '@type'          => 'Offer',
            'price'          => $this->normalizePrice($data['price']),
            'priceCurrency'  => $data['currency'],
            'url'            => $data['url'] ?? null,
        ];

        if (isset($data['in_stock'])) {
            $offer['availability'] = $data['in_stock']
                ? 'http://schema.org/InStock'
                : 'http://schema.org/OutOfStock';
        }

        return $offer;
    }

    private function mapReviews(array $reviews, ?string $productUrl): array
    {
        return array_map(function (array $r) use ($productUrl): array {
            $isBasedOn = $r['origin']['url'] ?? $productUrl;

            $entry = [
                '@type'         => 'Review',
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $r['user']['name'] ?? $r['user'] ?? $r['author'] ?? null,
                ],
                'datePublished' => $r['created_at'] ?? $r['datePublished'] ?? null,
                'reviewBody'    => $r['text'] ?? $r['reviewBody'] ?? null,
                'isBasedOn'     => $isBasedOn,
            ];

            // lipscore raw score is on a 10-point scale → divide by 2
            $rawRating   = $r['lipscore'] ?? null;
            $ratingValue = $rawRating !== null ? $rawRating / 2 : ($r['rating'] ?? null);

            if ($ratingValue > 0) {
                $entry['reviewRating'] = [
                    '@type'       => 'Rating',
                    'ratingValue' => $ratingValue,
                    'bestRating'  => self::RATING_BEST,
                    'worstRating' => self::RATING_WORST,
                ];
            }

            return $entry;
        }, $reviews);
    }

    private function filterGtins(array $gtins): array
    {
        return array_values(array_filter(
            $gtins,
            fn($g) => in_array(strlen((string) $g), self::VALID_GTIN_LENGTHS, true)
        ));
    }

    private function gtinName(string $gtin): string
    {
        return 'gtin' . strlen($gtin);
    }

    private function addGtins(array &$schema, array $validGtins): void
    {
        foreach ($validGtins as $gtin) {
            $key = $this->gtinName((string) $gtin);
            $schema[$key]   = $schema[$key] ?? [];
            $schema[$key][] = $gtin;
        }
    }

    private function addProductId(array &$schema, string $sku, string $mpn, array $validGtins): void
    {
        $productId = [];

        if ($sku !== '') {
            $productId[] = "sku:{$sku}";
        }
        if ($mpn !== '') {
            $productId[] = "mpn:{$mpn}";
        }
        foreach ($validGtins as $gtin) {
            $productId[] = $this->gtinName((string) $gtin) . ":{$gtin}";
        }

        if (!empty($productId)) {
            $schema['productID'] = $productId;
        }
    }

    private function firstValue(string $value): string
    {
        return explode(';', $value)[0];
    }

    private function normalizePrice(mixed $price): string
    {
        return number_format((float) str_replace(',', '.', (string) $price), 2, '.', '');
    }

    private function truncateDescription(string $description): string
    {
        return mb_substr(trim(strip_tags($description)), 0, self::MAX_DESC_LENGTH);
    }

    private function normalizeReviews(array $ratings): array
    {
        $raw = $ratings['last_reviews'] ?? [];

        return is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
    }

    private function getProductData(ProductInterface $product): array
    {
        try {
            return $this->helper->getProductData($product);
        } catch (\Throwable $e) {
            $this->logger->warning('Lipscore could not load product data for schema', [
                'product_id' => $product->getId(),
                'error'      => $e->getMessage(),
            ]);

            return [];
        }
    }
}
