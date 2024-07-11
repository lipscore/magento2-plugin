<?php

namespace Lipscore\RatingsReviews\Model\Api;

use Lipscore\RatingsReviews\Model\Env;

class Request
{
    protected $config;
    protected $path;
    protected $env;
    protected $requestType = 'POST';
    protected $timeout     = 5;
    protected $response;
    protected $client;

    /**
     * @param Env $env
     * @param $config
     * @param $path
     * @param $params
     */
    public function __construct(
        Env $env,
        $config,
        $path,
        $params = []
    ) {
        $this->env    = $env;
        $this->config = $config;
        $this->path   = $path;

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
            throw new Exception('No HTTP client library available.');
        }
    }

    public function send($data)
    {
        $apiKey = $this->config->apiKey();
        $secret = $this->config->secret();
        $apiUrl = $this->env->apiUrl();
        $headers = [
            'X-Authorization' => strval($secret),
            'Content-Type' => 'application/json',
        ];
        $url = "$apiUrl/{$this->path}?api_key=$apiKey";

        if ($this->client instanceof \Laminas\Http\Client) {
            $this->client->setUri($url);
            $this->client->setOptions(['timeout' => $this->timeout]);
            $this->client->setMethod($this->requestType);
            $this->client->setRawBody(json_encode($data));
            $this->client->setHeaders($headers);
            $this->response = $this->client->send();
            $result = $this->response->isSuccess() ? json_decode($this->response->getBody(), true) : false;
        } elseif ($this->client instanceof \GuzzleHttp\Client) {

            $options = [
                'headers' => $headers,
                'json' => $data,
                'timeout' => $this->timeout,
            ];
            $response = $this->client->request($this->requestType, $url, $options);
            $this->response = $response;
            $result = $response->getStatusCode() === 200 ? json_decode($response->getBody(), true) : false;

        } elseif ($this->client instanceof \Zend\Http\Client) {
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

    public function responseMsg()
    {
        return $this->response ? $this->response->__toString() : '';
    }
}
