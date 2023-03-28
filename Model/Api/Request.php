<?php

namespace Lipscore\RatingsReviews\Model\Api;

use Laminas\Http\Request;

class Request
{
    protected $config;
    protected $path;
    protected $env;
    protected $client;
    protected $requestType = Request::METHOD_POST;
    protected $timeout     = 5;
    protected $response;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Env $env,
        Magento\Framework\HTTP\LaminasClientFactory $clientFactory,
        $config,
        $path,
        $params = []
    ) {
        $this->env    = $env;
        $this->client = $clientFactory->create();
        $this->config = $config;
        $this->path   = $path;

        if (!empty($params['timeout'])) {
            $this->timeout = $params['timeout'];
        }

        if (!empty($params['requestType'])) {
            $this->requestType = $params['requestType'];
        }
    }

    public function send($data)
    {
        $apiKey = $this->config->apiKey();
        $secret = $this->config->secret();
        $apiUrl = $this->env->apiUrl();

        $this->client->setUri("$apiUrl/{$this->path}?api_key=$apiKey");
        $this->client->setOptions(['timeout' => $this->timeout]);
        $this->client->setMethod($this->requestType);
        $this->client->setRawBody(json_encode($data));
        $this->client->setHeaders([
            'X-Authorization' => $secret,
            'Content-Type'    => 'application/json'
        ]);

        $this->response = $this->client->send();

        $result = $this->response->isSuccess();
        if ($result) {
            $result = json_decode($this->response->getBody(), true);
        }

        return $result;
    }

    public function responseMsg()
    {
        return $this->response ? $this->response->__toString() : '';
    }
}
