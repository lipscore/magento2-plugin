<?php

namespace Lipscore\RatingsReviews\Model\Api;

use \Magento\Framework\HTTP\ZendClient;

class Request
{
    protected $config;
    protected $path;
    protected $env;
    protected $client;
    protected $requestType = ZendClient::POST;
    protected $timeout     = 10;
    protected $response;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Env $env,
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        $config,
        $path,
        $params = array()
    ){
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
        $apiUrl = $this->env->apiUrl();

        $this->client->setUri("$apiUrl/{$this->path}?api_key=$apiKey");
        $this->client->setConfig(['timeout' => $this->timeout]);
        $this->client->setMethod($this->requestType);
        $this->client->setRawData(json_encode($data), 'application/json');

        $this->response = $this->client->request();

        $result = $this->response->isSuccessful();
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
