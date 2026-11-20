<?php

namespace Lipscore\RatingsReviews\Model\Api;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\Exception\LocalizedException;

class Request
{
    /** Timeout in seconds, the REMINDER_TIMEOUT environment variable overrides it. */
    public const REMINDER_TIMEOUT = 5;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var string
     */
    protected $requestType = 'POST';

    /**
     * @var int
     */
    protected $timeout = self::REMINDER_TIMEOUT;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $client;

    /**
     * @var int|null
     */
    protected $storeId = null;

    /**
     * Request constructor.
     *
     * @param Config $config
     * @throws LocalizedException
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;

        if (class_exists(\Laminas\Http\Client::class)) {
            $this->client = new \Laminas\Http\Client();
        } elseif (class_exists(\GuzzleHttp\Client::class)) {
            $this->client = new \GuzzleHttp\Client();
        } elseif (class_exists(\Zend\Http\Client::class)) {
            $this->client = new \Zend\Http\Client();
        } else {
            throw new LocalizedException(__('No HTTP client library available.'));
        }
    }

    /**
     * POST JSON data to the Lipscore API of the store.
     *
     * Never throws on HTTP errors or timeouts: they are returned as a Response with the status code
     * (0 when no response was received), so the caller can decide whether to retry.
     *
     * @param string $path API path, e.g. "invitations"
     * @param array $data
     * @param int|null $storeId
     * @param int|null $timeout Seconds; defaults to REMINDER_TIMEOUT or the environment variable
     * @return Response
     * @throws \InvalidArgumentException When the data cannot be encoded as JSON
     */
    public function post($path, array $data, $storeId = null, $timeout = null)
    {
        $body = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \InvalidArgumentException('Lipscore API request data cannot be encoded as JSON: '
                . json_last_error_msg());
        }

        $this->path = $path;
        $this->timeout = $timeout ? (int) $timeout : $this->getDefaultTimeout();
        $url = sprintf(
            '%s/%s?api_key=%s',
            rtrim((string) $this->config->getApiUrl($storeId), '/'),
            ltrim($path, '/'),
            rawurlencode((string) $this->config->getApiKey($storeId))
        );
        $headers = [
            'X-Authorization' => (string) $this->config->getApiSecret($storeId),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        try {
            if ($this->client instanceof \GuzzleHttp\Client) {
                $this->response = $this->client->request('POST', $url, [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => $this->timeout,
                    'http_errors' => false,
                ]);
            } else {
                $this->client->resetParameters(true);
                $this->client->setUri($url);
                $this->client->setOptions(['timeout' => $this->timeout]);
                $this->client->setMethod('POST');
                $this->client->setRawBody($body);
                $this->client->setHeaders($headers);
                $this->response = $this->client->send();
            }
        } catch (\Exception $e) {
            // Timeouts and connection failures of every supported client.
            $this->response = null;
            return new Response(0, '', $e);
        }

        return new Response((int) $this->response->getStatusCode(), (string) $this->response->getBody());
    }

    /**
     * Send data to the Lipscore API and return the decoded response.
     *
     * @deprecated 2.3.0 Does not tell temporary errors from permanent ones.
     * @see Request::post()
     * @param mixed $data
     * @param string|null $path
     * @return array|false
     */
    public function send($data, $path = null)
    {
        $response = $this->post($path ?: 'purchases', (array) $data, $this->storeId);
        if (!$response->isSuccess()) {
            return false;
        }

        return $response->getData() ?? [];
    }

    /**
     * Set the store id used by send().
     *
     * @param int|null $storeId
     * @return void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Return the last raw response as a string.
     *
     * @return string
     */
    public function responseMsg()
    {
        if (!$this->response) {
            return '';
        }

        return method_exists($this->response, 'toString')
            ? $this->response->toString()
            : (string) $this->response->getBody();
    }

    /**
     * Default timeout in seconds.
     *
     * @return int
     */
    protected function getDefaultTimeout()
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $timeout = (int) getenv('REMINDER_TIMEOUT');

        return $timeout > 0 ? $timeout : static::REMINDER_TIMEOUT;
    }
}
