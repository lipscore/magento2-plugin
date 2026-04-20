<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Service\JsonLd;

use Lipscore\RatingsReviews\Model\Api\ProductRequest;
use Lipscore\RatingsReviews\Model\ApiKeyRepository;
use Lipscore\RatingsReviews\Model\Cache\FpcRefresher;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\JsonLd\ProductJsonLdRepository;
use Lipscore\RatingsReviews\Model\QueueRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class LipscoreReadService
{
    private const CACHE_KEY_RATINGS = 'lipscore_ratings_';
    private const CACHE_TAG = 'LIPSCORE_DATA';

    protected $cache;

    protected $fpcRefresher;

    protected $repository;

    protected $queueRepository;

    protected $apiKeyRepository;

    protected $request;

    protected $config;
    protected $jsonLdBuilder;

    protected $logger;

    public function __construct(
        CacheInterface $cache,
        FpcRefresher $fpcRefresher,
        ProductJsonLdRepository $repository,
        QueueRepository $queueRepository,
        ApiKeyRepository $apiKeyRepository,
        ProductRequest $request,
        Config $config,
        JsonLdBuilder $jsonLdBuilder,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->repository = $repository;
        $this->queueRepository = $queueRepository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->request = $request;
        $this->config = $config;
        $this->jsonLdBuilder = $jsonLdBuilder;
        $this->fpcRefresher = $fpcRefresher;
        $this->logger = $logger;
    }

    public function getJsonLdProduct(ProductInterface $product): ?array
    {
        $ratings = $this->getRatings($product);

        return $ratings
            ? $this->jsonLdBuilder->build($product, $ratings)
            : null;
    }

    public function getJsonLdProductReviews(ProductInterface $product): ?array
    {
        $ratings = $this->getRatings($product);

        return $ratings
            ? $this->jsonLdBuilder->buildReviews($product, $ratings)
            : null;
    }

    private function getRatings(ProductInterface $product): ?array
    {
        $productId = (int)$product->getId();
        $keyId = $this->apiKeyRepository->getIdByKey($this->config->getApiKey());
        if (!$keyId) {
            return null;
        }

        $cacheKey = $this->getCacheKey($productId, $keyId);

        // L1 Cache
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false) {
            $decoded = json_decode($cached, true);
            return is_array($decoded) ? $decoded : null;
        }

        // L2 DB
        $row = $this->repository->getProductData($productId, $keyId);

        if (!$row) {
            $this->enqueue($productId, $keyId, QueueRepository::PRIORITY_HIGH);
            return null;
        }

        $age   = time() - strtotime((string)$row['updated_at']);
        $stale = $age > $this->config->getFreshThreshold();

        $ratings = $this->extractRatings($row, $stale);

        $this->saveToCache($cacheKey, $ratings);

        if ($age > $this->config->getStaleThreshold()) {
            $this->enqueue($productId, $keyId, QueueRepository::PRIORITY_HIGH);
        } elseif ($stale) {
            $this->enqueue($productId, $keyId, QueueRepository::PRIORITY_LOW);
        }

        return $ratings;
    }

    public function fetchAndSaveBatch(array $productIds, array $context): array
    {
        if (!isset($context['key_id'])) {
            return ['saved' => []];
        }

        $apiResults = $this->request->fetchProductsBatch($productIds, $context);
        $saved      = [];

        foreach ($apiResults as $data) {
            $productId = (int)($data['magento_product_id'] ?? 0);
            if (!$productId) {
                continue;
            }
            try {
                $this->repository->upsert($productId, $context['key_id'], $data);
                $this->invalidateCache($productId, $context);
                $saved[] = $productId;
            } catch (\Throwable $e) {
                $this->logger->error('Lipscore save failed', [
                    'product_id' => $productId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return ['saved' => $saved];
    }

    private function extractRatings(array $row, bool $stale): array
    {
        $reviews = json_decode((string)($row['last_reviews'] ?? '[]'), true);

        return [
            'rating_value' => $row['rating_value'] ?? null,
            'review_count' => (int)($row['review_count'] ?? 0),
            'votes_count'  => (int)($row['rating_count'] ?? 0),
            'last_reviews' => is_array($reviews) ? $reviews : [],
            'stale'        => $stale,
        ];
    }

    private function enqueue(int $productId, int $keyId, int $priority): void
    {
        try {
            $this->queueRepository->enqueue($productId, $keyId, $priority);
        } catch (\Throwable $e) {
            $this->logger->error('Lipscore enqueue failed', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function saveToCache(string $key, array $data): void
    {
        $this->cache->save(
            json_encode($data, JSON_THROW_ON_ERROR),
            $key,
            [self::CACHE_TAG],
            $this->config->getCacheTtl()
        );
    }

    private function invalidateCache(int $productId, $context): void
    {
        $this->cache->remove($this->getCacheKey($productId, $context['key_id']));
        if ($this->config->isFpcClearEnabled()) {
            $this->fpcRefresher->refreshProduct($productId);
        }
    }

    private function getCacheKey(int $productId, int $keyId): string
    {
        return self::CACHE_KEY_RATINGS . $keyId . '_' . $productId;
    }
}
