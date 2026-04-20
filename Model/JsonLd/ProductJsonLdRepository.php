<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\JsonLd;

use Magento\Framework\App\ResourceConnection;

class ProductJsonLdRepository
{
    private \Magento\Framework\DB\Adapter\AdapterInterface $connection;
    private string $table;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();
        $this->table      = $resource->getTableName('lipscore_product_data');
    }

    /**
     * Insert or update from raw Lipscore API response
     */
    public function upsert(
        int $productId,
        int $keyId,
        array $apiData
    ): void {
        $reviews = $apiData['reviews'] ?? [];

        usort(
            $reviews,
            static fn(array $a, array $b): int =>
                strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '')
        );

        $data = [
            'product_id'   => $productId,
            'key_id'       => $keyId,
            'rating_value' => $apiData['rating'] ?? null,
            'review_count' => (int) ($apiData['review_count'] ?? 0),
            'rating_count' => (int) ($apiData['votes'] ?? 0),
            'last_reviews' => json_encode(array_slice($reviews, 0, 10)),
        ];

        $this->connection->insertOnDuplicate(
            $this->table,
            $data,
            [
                'rating_value',
                'review_count',
                'rating_count',
                'last_reviews',
                'key_id',
                'product_id',
                'updated_at'
            ]
        );
    }

    public function getProductData(
        int $productId,
        int $keyId
    ): ?array {
        $select = $this->connection->select()
            ->from($this->table)
            ->where('product_id = ?', $productId)
            ->where('key_id = ?', $keyId)
            ->limit(1);

        $row = $this->connection->fetchRow($select);

        return $row ?: null;
    }
}
