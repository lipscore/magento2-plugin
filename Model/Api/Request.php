<?php

namespace Lipscore\RatingsReviews\Model\Api;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\Exception\LocalizedException;

class Request
{
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
    protected $timeout     = 5;

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
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;

        if (!empty($params['timeout'])) {
            $this->timeout = $params['timeout'];
        }

        if (!empty($params['requestType'])) {
            $this->requestType = $params['requestType'];
        }

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
     * Send data to the Lipscore API and return the decoded response.
     *
     * @param mixed $data
     * @param string|null $path
     * @return array|false
     */
    public function send($data, $path = null)
    {
        if (!$path) {
            $path = 'purchases';
        }
        $this->path = $path;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $timeout = getenv('REMINDER_TIMEOUT');
        $this->timeout =  $timeout ?: static::REMINDER_TIMEOUT;

        $apiKey = $this->config->getApiKey($this->storeId);
        $secret = $this->config->getApiSecret($this->storeId);
        $apiUrl = $this->config->getApiUrl($this->storeId);
        $headers = [
            'X-Authorization' => (string) $secret,
            'Content-Type'    => 'application/json',
        ];
        $url = "$apiUrl/{$this->path}?api_key=$apiKey";

        if ($this->client instanceof \GuzzleHttp\Client) {
            $options = [
                'headers' => $headers,
                'json'    => $data,
                'timeout' => $this->timeout,
            ];
            $response = $this->client->request($this->requestType, $url, $options);
            $this->response = $response;
            $result = $response->getStatusCode() === 200 ? json_decode($response->getBody(), true) : false;
        } else {
            $this->client->setUri($url);
            $this->client->setOptions(['timeout' => $this->timeout]);
            $this->client->setMethod($this->requestType);
            $this->client->setRawBody(json_encode($data));
            $this->client->setHeaders($headers);
            $this->response = $this->client->send();
            $result = $this->response->isSuccess() ? json_decode($this->response->getBody(), true) : false;
        }
        return $result;
    }

    /**
     * Set the store id used for API requests.
     *
     * @param int|null $storeId
     * @return void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Return the raw response body as a string.
     *
     * @return string
     */
    public function responseMsg()
    {
        return $this->response ? $this->response->__toString() : '';
    }
}
