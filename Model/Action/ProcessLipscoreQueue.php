<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Action;

use Lipscore\RatingsReviews\Model\ApiKeyRepository;
use Lipscore\RatingsReviews\Model\QueueRepository;
use Lipscore\RatingsReviews\Model\Service\JsonLd\LipscoreReadService;
use Psr\Log\LoggerInterface;

class ProcessLipscoreQueue
{
    private const BATCH_SIZE  = 20;
    private const MAX_BATCHES = 10;

    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private QueueRepository $queueRepository,
        private LipscoreReadService $readService,
        private LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        $start = microtime(true);
        $keys = $this->apiKeyRepository->collectAllKeys(); // [id => apiKey]

        try {
            $apiKeys = $this->apiKeyRepository->buildKeyContextMap($keys);
            if (!$apiKeys) {
                $this->logger->warning('Lipscore: no API keys configured');
                return;
            }

            $totalBatches = 0;
            $totalItems   = 0;

            foreach ($apiKeys as $context) {
                [$batches, $items] = $this->processForKey($context);
                $totalBatches += $batches;
                $totalItems   += $items;
            }

            $this->logger->info('Lipscore queue complete', [
                'keys'       => count($apiKeys),
                'batches'    => $totalBatches,
                'items'      => $totalItems,
                'duration_s' => round(microtime(true) - $start, 2),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Lipscore queue cron error', [
                'error_class' => get_class($e),
                'message'     => $e->getMessage(),
            ]);
        }
    }

    private function processForKey(array $keyContext): array
    {
        $keyId = $this->apiKeyRepository->getIdByKey($keyContext['api_key']);
        if (!$keyId) {
            return [0, 0];
        }

        $keyContext['key_id'] = $keyId;

        $batches = 0;
        $total   = 0;

        do {
            $rows = $this->queueRepository->dequeue($keyId, self::BATCH_SIZE);
            if (!$rows) {
                break;
            }

            $productIds = array_map(
                static fn(array $row): int => (int)$row['product_id'],
                $rows
            );

            $this->logger->debug('Lipscore queue batch', [
                'key_id'      => $keyId,
                'product_ids' => $productIds,
            ]);

            try {
                $result = $this->readService->fetchAndSaveBatch($productIds, $keyContext);
                $saved  = $result['saved'] ?? [];

                foreach ($rows as $row) {
                    $queueId   = (int)$row['queue_id'];
                    $productId = (int)$row['product_id'];
                    $success   = in_array($productId, $saved, true);

                    $this->queueRepository->markProcessed($queueId, $success);

                    if (!$success) {
                        $this->logger->warning('Lipscore: no data for product', [
                            'product_id' => $productId,
                        ]);
                    }

                    $total++;
                }

            } catch (\Throwable $e) {
                foreach ($rows as $row) {
                    $this->queueRepository->markProcessed(
                        (int)$row['queue_id'],
                        false
                    );
                }

                $this->logger->error('Lipscore batch failed', [
                    'api_key'     => substr($keyContext['api_key'], 0, 4) . '****',
                    'error_class' => get_class($e),
                    'error'       => $e->getMessage(),
                ]);
            }

            $batches++;
            usleep(100000); // 100ms throttle

        } while ($batches < self::MAX_BATCHES);

        return [$batches, $total];
    }
}
