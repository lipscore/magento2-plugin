<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Model\Api\HookRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ApiKeyRepository
{
    private const TABLE_NAME = 'lipscore_api_key';

    protected $resource;

    protected $storeManager;

    protected $config;

    protected $request;

    protected $logger;

    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        Config $config,
        HookRequest $request,
        LoggerInterface $logger
    ) {
        $this->resource     = $resource;
        $this->storeManager = $storeManager;
        $this->config       = $config;
        $this->request      = $request;
        $this->logger       = $logger;
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }

    private function getTable(): string
    {
        return $this->resource->getTableName(self::TABLE_NAME);
    }

    public function getAllKeys(): array
    {
        return $this->getConnection()->fetchPairs(
            sprintf('SELECT api_key_id, `key` FROM %s', $this->getTable())
        );
    }

    public function getIdByKey(?string $key): ?int
    {
        if (!$key) {
            return null;
        }

        $id = $this->getConnection()->fetchOne(
            sprintf('SELECT api_key_id FROM %s WHERE `key` = ? LIMIT 1', $this->getTable()),
            [$key]
        );

        return $id !== false ? (int)$id : null;
    }

    public function deleteHooksForAllKeys(): void
    {
        $activeMap = $this->buildKeyContextMap($this->collectAllKeys());
        foreach ($activeMap as $apiKey => $context) {
            $this->request->deregisterAllWebhooks($apiKey, $context);
        }
    }

    public function syncKeys(): void
    {
        $connection = $this->getConnection();
        $table      = $this->getTable();

        $activeMap = $this->buildKeyContextMap($this->collectAllKeys());
        $existing  = $connection->fetchPairs(
            sprintf('SELECT `key`, api_key_id FROM %s', $table)
        );

        $toAdd    = array_diff(array_keys($activeMap), array_keys($existing));
        $toRemove = array_diff(array_keys($existing), array_keys($activeMap));

        foreach ($toAdd as $apiKey) {
            $context = $activeMap[$apiKey];
            $this->registerKey($apiKey, $context);
        }

        foreach ($toRemove as $apiKey) {
            $context = $activeMap[$apiKey];
            $this->removeKey($apiKey, $context);
        }
    }

    public function collectAllKeys(): array
    {
        $keys = [];

        // Default scope
        $defaultKey = $this->config->getApiKeyForScope('default');
        if ($this->config->isWebhookEnabledForScope('default') && $defaultKey) {
            $keys[] = [
                'scope' => 'default',
                'scope_id' => 0,
                'api_key' => trim($defaultKey),
            ];
        }

        // Website scope
        foreach ($this->storeManager->getWebsites() as $website) {
            $websiteId = (int)$website->getId();
            $key = $this->config->getApiKeyForScope(ScopeInterface::SCOPE_WEBSITE, $websiteId);

            if ($this->config->isWebhookEnabledForScope(ScopeInterface::SCOPE_WEBSITE, $websiteId) && $key) {
                $keys[] = [
                    'scope' => 'website',
                    'scope_id' => $websiteId,
                    'api_key' => trim($key),
                ];
            }
        }

        // Store scope
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            $key = $this->config->getApiKeyForScope(ScopeInterface::SCOPE_STORE, $storeId);

            if ($this->config->isWebhookEnabledForScope(ScopeInterface::SCOPE_STORE, $storeId) && $key) {
                $keys[] = [
                    'scope' => 'store',
                    'scope_id' => $storeId,
                    'api_key' => trim($key),
                ];
            }
        }

        return $keys;
    }

    public function buildKeyContextMap(array $entries): array
    {
        $priorityMap = ['default' => 0, 'website' => 1, 'store' => 2];
        $result      = [];

        foreach ($entries as $entry) {
            $apiKey   = $entry['api_key'];
            $priority = $priorityMap[$entry['scope']] ?? 0;

            if (isset($result[$apiKey]) && $result[$apiKey]['priority'] >= $priority) {
                continue;
            }

            $context = $this->resolveContext($entry['scope'], $entry['scope_id']);

            $result[$apiKey] = [
                'api_key' => $apiKey,
                'base_url'   => $context['base_url'],
                'api_secret' => $context['api_secret'],
                'api_url' => $context['api_url'],
                'priority'   => $priority,
            ];
        }

        return $result;
    }

    private function resolveContext(string $scope, int $scopeId): array
    {
        return match ($scope) {
            'store'   => $this->resolveStore($scopeId),
            'website' => $this->resolveWebsite($scopeId),
            default   => $this->resolveDefault(),
        };
    }

    private function resolveStore(int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);

        return [
            'base_url'   => $store->getBaseUrl(),
            'api_secret' => (string)$this->config->getApiSecretForScope(
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'api_url' => $this->config->getApiUrl($storeId),
        ];
    }

    private function resolveWebsite(int $websiteId): array
    {
        $website = $this->storeManager->getWebsite($websiteId);
        $defaultStore = $website->getDefaultStore();

        if (!$defaultStore) {
            throw new \RuntimeException("Website {$websiteId} has no default store.");
        }

        return [
            'base_url'   => $defaultStore->getBaseUrl(),
            'api_secret' => (string)$this->config->getApiSecretForScope(
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            ),
            'api_url' => $this->config->getApiKeyForScope(
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            ),
        ];
    }

    private function resolveDefault(): array
    {
        $defaultStore = $this->storeManager->getDefaultStoreView();

        return [
            'base_url'   => $defaultStore->getBaseUrl(),
            'api_secret' => (string)$this->config->getApiSecretForScope('default'),
            'api_url' => $this->config->getApiKeyForScope(
                'default'
            ),
        ];
    }

    private function registerKey(string $apiKey, $context): void
    {
        try {
            $result = $this->request->registerAllWebhooks($apiKey, $context);

            $ids = array_map(function($item) { return $item['id']; }, $result['registered']);
            $this->getConnection()->insert(
                $this->getTable(),
                ['key' => $apiKey, 'external_hook_ids' => $ids ? implode(',', $ids) : '']
            );

            $this->logger->info('Lipscore webhooks registered', [
                'key'        => $this->maskKey($apiKey),
                'store_url'  => $context['base_url'],
                'registered' => $result['registered'] ?? 0,
                'skipped'    => $result['skipped'] ?? 0,
                'failed'     => $result['failed'] ?? 0,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Lipscore webhook registration failed', [
                'key'   => $this->maskKey($apiKey),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function removeKey(int $keyId, $context): void
    {
        $externalHookIds = $this->getConnection()->fetchOne(
            $this->getConnection()
                ->select()
                ->from($this->getTable(), ['external_hook_ids'])
                ->where('api_key_id = ?', $keyId)
                ->limit(1)
        );

        if ($externalHookIds) {
            $this->request->deregisterSelectedWebhooks(explode(',', $externalHookIds), $context);
        }

        $this->getConnection()->delete(
            $this->getTable(),
            ['api_key_id = ?' => $keyId]
        );

        $this->logger->info('Lipscore API key removed', ['key_id' => $keyId]);
    }

    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return '****';
        }

        return substr($key, 0, 4) . '****' . substr($key, -4);
    }
}
