<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Api;

use GuzzleHttp\ClientInterface;
use Lipscore\RatingsReviews\Exception\ApiException;

class HookRequest
{
    private const WEBHOOK_EVENTS = [
        'rating_created',
        'review_created',
    ];

    private const WEBHOOK_PATH = '/rest/V1/lipscore/product/webhook';

    protected $client;

    public function __construct(
        ClientInterface $client
    ) {
        $this->client = $client;
    }

    public function registerAllWebhooks(string $apiKey, $context): array
    {
        $results = ['registered' => [], 'failed' => []];

        foreach (self::WEBHOOK_EVENTS as $event) {
            try {
                $id = $this->registerWebhook($apiKey, $event, $context);
                $results['registered'][] = ['event' => $event, 'id' => $id];
            } catch (\Throwable $e) {
                $results['failed'][] = ['event' => $event, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function deregisterSelectedWebhooks($hookIds, $context): array
    {
        $results = ['deregistered' => [], 'failed' => []];
        if (!is_array($hookIds)) {
            return $results;
        }

        foreach ($hookIds as $hookId) {
            try {
                $this->deleteWebhook($hookId, $context);
                $results['deregistered'][] = $hookId;
            } catch (\Throwable $e) {
                $results['failed'][] = ['id' => $hookId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function deregisterAllWebhooks(string $apiKey, $context): array
    {
        $results = ['deregistered' => [], 'failed' => []];

        foreach ($this->fetchAllWebhooks($apiKey, $context) as $hook) {
            $hookId = (string)($hook['id'] ?? 0);

            if ($hookId == 0) {
                continue;
            }

            try {
                $this->deleteWebhook($apiKey, $hookId, $context);
                $results['deregistered'][] = $hookId;
            } catch (\Throwable $e) {
                $results['failed'][] = ['id' => $hookId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function registerWebhook(string $apiKey, string $event, $context): string
    {
        $url = $this->buildUrl($context['api_url'], '/hooks', $apiKey);

        $response = $this->client->post($url, [
            'headers' => $this->defaultHeaders($context['api_secret']),
            'json'    => [
                'event'      => $event,
                'target_url' => rtrim($context['base_url'], '/') . self::WEBHOOK_PATH,
            ],
        ]);

        $status = $response->getStatusCode();

        if (!in_array($status, [201, 409], true)) {
            throw new ApiException(
                "Webhook registration failed for [{$event}], HTTP {$status}"
            );
        }

        $result = $this->decodeResponse((string)$response->getBody());
        if (isset($result['id'])) {
            return (string)$result['id'];
        }

        throw new ApiException(
            "Webhook registration failed for [{$event}], no ID returned"
        );
    }

    private function fetchAllWebhooks(string $apiKey, $context): array
    {
        $url      = $this->buildUrl($context['api_url'], '/hooks', $apiKey);
        $response = $this->client->get($url, ['headers' => $this->defaultHeaders($context['api_secret'])]);
        $status   = $response->getStatusCode();

        if ($status !== 200) {
            throw new ApiException("Fetching webhooks failed, HTTP {$status}");
        }

        return $this->decodeResponse((string)$response->getBody());
    }

    private function deleteWebhook(string $hookId, $context): void
    {
        $url      = $this->buildUrl($context['api_url'], "/hooks/{$hookId}", $context['api_key']);
        $response = $this->client->delete($url, ['headers' => $this->defaultHeaders($context['api_secret'])]);
        $status   = $response->getStatusCode();

        if (!in_array($status, [200, 204], true)) {
            throw new ApiException(
                "Webhook deletion failed for hook ID [{$hookId}], HTTP {$status}"
            );
        }
    }

    private function buildUrl(string $baseUrl, string $path, string $apiKey): string
    {
        return rtrim($baseUrl, '/') . $path . '?api_key=' . urlencode($apiKey);
    }

    private function defaultHeaders(string $apiSecret): array
    {
        return [
            'X-Authorization' => $apiSecret,
            'Accept'          => 'application/json',
            'Content-Type'    => 'application/json',
        ];
    }

    private function decodeResponse(string $body): array
    {
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
        }

        return is_array($data) ? $data : [];
    }
}
