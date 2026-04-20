<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Api\WebhookManagementInterface;
use Lipscore\RatingsReviews\Model\Service\JsonLd\ProductResolver;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class WebhookManagement implements WebhookManagementInterface
{
    protected $queueRepository;

    protected $apiKeyRepository;

    protected $config;

    protected $request;

    protected $productResolver;

    protected $logger;

    public function __construct(
        QueueRepository $queueRepository,
        ApiKeyRepository $apiKeyRepository,
        Config $config,
        Http $request,
        ProductResolver $productResolver,
        LoggerInterface $logger
    ) {
        $this->queueRepository = $queueRepository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->config = $config;
        $this->request = $request;
        $this->productResolver = $productResolver;
        $this->logger = $logger;
    }

    public function handle(): bool
    {
        if (!$this->config->isWebhookEnabled()) {
            $this->logger->info(
                'Lipscore webhook disabled; responding 410 Gone to trigger provider-side deregistration'
            );
            throw new GoneHttpException('Webhook disabled');
        }

        $body = $this->request->getContent();
        $payload = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LocalizedException(__('Invalid JSON payload'));
        }

        $this->verifySignature($body, $this->request->getHeader('Lipscore-Hmac-Sha256-Base64'));

        $internalId = trim((string)($payload['product']['internal_id'] ?? ''));
        $event = trim((string)($payload['event'] ?? ''));

        if (empty($internalId) || empty($event)) {
            $this->logger->warning('Lipscore webhook missing fields', [
                'keys' => array_keys($data ?? []),
            ]);
            throw new LocalizedException(__('Missing product.internal_id or event'));
        }

        $productId = $this->productResolver->resolveProductId($internalId);

        if (!$productId) {
            $this->logger->warning('Lipscore webhook product not found', [
                'internal_id' => $internalId,
            ]);
            return true;
        }

        $priority = in_array($event, ['rating_created', 'review_created'])
            ? QueueRepository::PRIORITY_HIGH
            : QueueRepository::PRIORITY_LOW;
        $apiKeyId = $this->apiKeyRepository->getIdByKey($this->config->getApiKey());
        $this->queueRepository->enqueue($productId, $apiKeyId, $priority);

        $this->logger->info('Lipscore webhook enqueued', [
            'internal_id' => $internalId,
            'product_id' => $productId,
            'event' => $event,
            'priority' => $priority,
        ]);

        return true;
    }
    private function verifySignature(string $payload, $signature): void
    {
        $secret = $this->config->getWebhookHmacSecret();
        if (!$secret || !$signature) {
            return;
        }

        if (ctype_xdigit($secret) && strlen($secret) === 128) {
            $secret = hex2bin($secret);
        }

        $expected = base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        if (!hash_equals($expected, $signature)) {
            $this->logger->warning('Lipscore webhook signature mismatch');
            throw new LocalizedException(__('Invalid signature'));
        }
    }

}
