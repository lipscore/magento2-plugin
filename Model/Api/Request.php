<?php

namespace Lipscore\RatingsReviews\Model\Api;

class Request
{
    protected $config;
    protected $path;
    protected $env;
    protected $requestType = \Laminas\Http\Request::METHOD_POST;
    protected $timeout     = 5;
    protected $response;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Env $env,
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
    }

    public function send($data)
    {
        $apiKey = $this->config->apiKey();
        $secret = $this->config->secret();
        $apiUrl = $this->env->apiUrl();

        $client = new \Laminas\Http\Client();

        $client->setUri("$apiUrl/{$this->path}?api_key=$apiKey");
        $client->setOptions(['timeout' => $this->timeout]);
        $client->setMethod($this->requestType);
        $client->setRawBody(json_encode($data));
        $client->setHeaders([
            'X-Authorization' => strval($secret),
            'Content-Type'    => 'application/json'
        ]);

        $this->response = $client->send();

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
