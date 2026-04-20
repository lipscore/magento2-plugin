<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Cache;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cache\Tag\Resolver as CacheTagResolver;
use Magento\PageCache\Model\Cache\Type;
use Magento\PageCache\Model\Config as PageCacheConfig;
use Magento\CacheInvalidate\Model\PurgeCache;
use Psr\Log\LoggerInterface;

class FpcRefresher
{
    private const PRODUCT_TAG_PREFIX = 'cat_p_';

    protected $logger;

    protected $productRepository;

    protected $frontendPool;

    protected $cacheTagResolver;

    protected $pageCacheConfig;

    protected $purgeCache;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        FrontendPool              $frontendPool,
        CacheTagResolver          $cacheTagResolver,
        PageCacheConfig           $pageCacheConfig,
        PurgeCache                $purgeCache,
        LoggerInterface           $logger
    ) {
        $this->productRepository = $productRepository;
        $this->frontendPool      = $frontendPool;
        $this->cacheTagResolver  = $cacheTagResolver;
        $this->pageCacheConfig   = $pageCacheConfig;
        $this->purgeCache        = $purgeCache;
        $this->logger            = $logger;
    }

    public function refreshProduct(int $productId): void
    {
        try {
            $tags = $this->resolveProductCacheTags($productId);

            $this->logger->info('Flushing FPC for product', [
                'product_id' => $productId,
                'backend'    => $this->isVarnish() ? 'varnish' : 'builtin',
                'tags'       => $tags,
            ]);

            if ($this->isVarnish()) {
                $this->flushVarnish($tags);
            } else {
                $this->flushBuiltin($tags);
            }

        } catch (\Throwable $e) {
            $this->logger->error('FPC flush failed', [
                'product_id' => $productId,
                'exception'  => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "FPC flush failed for product [{$productId}]: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    private function flushVarnish(array $tags): void
    {
        $this->purgeCache->sendPurgeRequest(implode('|', $tags));
    }

    private function flushBuiltin(array $tags): void
    {
        $this->frontendPool
            ->get(Type::TYPE_IDENTIFIER)
            ->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
    }

    private function isVarnish(): bool
    {
        return $this->pageCacheConfig->getType() === PageCacheConfig::VARNISH;
    }

    private function resolveProductCacheTags(int $productId): array
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId, false);
            $tags    = $this->cacheTagResolver->getTags($product);

            if (!empty($tags)) {
                return array_values($tags);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Could not resolve cache tags, using fallback', [
                'product_id' => $productId,
                'exception'  => $e->getMessage(),
            ]);
        }

        return [self::PRODUCT_TAG_PREFIX . $productId];
    }
}
