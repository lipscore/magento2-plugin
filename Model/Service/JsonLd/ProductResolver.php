<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Service\JsonLd;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\App\ResourceConnection;

class ProductResolver
{
    private static array $attributeCache = [];

    protected $connection;

    protected $config;

    protected $resource;

    public function __construct(
        Config $config,
        ResourceConnection $resource
    ) {
        $this->config = $config;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    public function resolveProductId(string $internalId): ?int
    {
        return match ($this->getAttributeCode()) {
            'id'    => is_numeric($internalId) ? (int) $internalId : null,
            'sku'   => $this->resolveBySku($internalId),
            default => $this->resolveByEavAttribute($this->getAttributeCode(), $internalId),
        };
    }

    public function getMagentoToLipscoreMap(array $magentoIds): array
    {
        if (empty($magentoIds)) {
            return [];
        }

        return match ($this->getAttributeCode()) {
            'id'    => array_combine(
                array_map('strval', $magentoIds),
                array_map('strval', $magentoIds)
            ),
            'sku'   => $this->mapBySku($magentoIds),
            default => $this->mapByEavAttribute($this->getAttributeCode(), $magentoIds),
        };
    }

    private function resolveBySku(string $sku): ?int
    {
        $select = $this->connection->select()
            ->from($this->table('catalog_product_entity'), ['entity_id'])
            ->where('sku = ?', $sku)
            ->limit(1);

        $id = $this->connection->fetchOne($select);
        return $id ? (int) $id : null;
    }

    private function mapBySku(array $magentoIds): array
    {
        $select = $this->connection->select()
            ->from($this->table('catalog_product_entity'), ['entity_id', 'sku'])
            ->where('entity_id IN (?)', $magentoIds);

        $map = [];
        foreach ($this->connection->fetchAll($select) as $row) {
            if (!empty($row['sku'])) {
                $map[(string) $row['entity_id']] = $row['sku'];
            }
        }

        return $map;
    }

    private function resolveByEavAttribute(string $attributeCode, string $value): ?int
    {
        ['id' => $attributeId, 'table' => $table] = $this->getAttributeMeta($attributeCode);

        $select = $this->connection->select()
            ->from($table, ['entity_id'])
            ->where('attribute_id = ?', $attributeId)
            ->where('value = ?', $value)
            ->limit(1);

        $id = $this->connection->fetchOne($select);
        return $id ? (int) $id : null;
    }

    private function mapByEavAttribute(string $attributeCode, array $magentoIds): array
    {
        ['id' => $attributeId, 'table' => $table] = $this->getAttributeMeta($attributeCode);

        $select = $this->connection->select()
            ->from($table, ['entity_id', 'value'])
            ->where('attribute_id = ?', $attributeId)
            ->where('entity_id IN (?)', $magentoIds);

        $map = [];
        foreach ($this->connection->fetchAll($select) as $row) {
            $value = trim((string) $row['value']);
            if ($value !== '') {
                $map[(string) $row['entity_id']] = $value;
            }
        }

        return $map;
    }

    private function getAttributeCode(): string
    {
        return $this->config->getProductAttributeId() ?: 'id';
    }

    private function getAttributeMeta(string $attributeCode): array
    {
        if (!isset(self::$attributeCache[$attributeCode])) {
            $select = $this->connection->select()
                ->from($this->table('eav_attribute'), ['attribute_id', 'backend_type'])
                ->where('entity_type_id = ?', 4)
                ->where('attribute_code = ?', $attributeCode)
                ->limit(1);

            $row = $this->connection->fetchRow($select);

            if (!$row) {
                throw new \RuntimeException(
                    "Lipscore: attribute '{$attributeCode}' not found."
                );
            }

            self::$attributeCache[$attributeCode] = [
                'id'    => (int) $row['attribute_id'],
                'table' => $this->table(match ($row['backend_type']) {
                    'text'    => 'catalog_product_entity_text',
                    'int'     => 'catalog_product_entity_int',
                    'decimal' => 'catalog_product_entity_decimal',
                    default   => 'catalog_product_entity_varchar',
                }),
            ];
        }

        return self::$attributeCache[$attributeCode];
    }

    private function table(string $name): string
    {
        return $this->resource->getTableName($name);
    }
}
