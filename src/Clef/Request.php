<?php

namespace Clef;

class Request
{
    /**
     * URL for API request
     * @var string
     */
    private $url = 'https://clef.io/api/v1';

    /**
     * Current HTTP client (probably Guzzle)
     * @var object
     */
    private $client = null;

    private $appId = null;

    private $appSecret = null;

    private $accessToken = null;

    private $userCode = null;

    public function setUserCode($code)
    {
        $this->userCode = $code;
        return $this;
    }

    public function getUserCode()
    {
        return $this->userCode;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
        return $this;
    }
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }
    public function getAppId()
    {
        return $this->appId;
    }

    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
        return $this;
    }
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * Get the current URL setting
     *
     * @return string URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the current URL value
     *
     * @param string $url Base API URL
     */
    public function setUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== $url) {
            throw new \InvalidArgumentException('URL "'.$url.'" is not valid');
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Set the HTTP client for requests (probably Guzzle)
     *
     * @param object $client HTTP client
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the current HTTP client
     *
     * @return object HTTP client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Geenrate the request signature
     *
     * @return string Request signature hash
     */
    public function generateSignature()
    {
        if ($this->getApiSecret() !== null) {
            $signatureString = $this->getApiKey().$this->getApiSecret().time();
        } else {
            $signatureString = $this->getApiKey().time();
        }

        return md5($signatureString);
    }

    public function authenticate()
    {
        $userCode = $this->getUserCode();

        if ($userCode === null) {
            throw new \InvalidArgumentException('User code cannot be null');
        }

        $client = $this->getClient();
        $params = array(
            'code' => $userCode,
            'app_id' => $this->getAppId(),
            'app_secret' => $this->getAppSecret()
        );

        $request = $client->post($this->getUrl().'/info/authorize');
        $request->setBody($data, 'application/x-www-form-urlencoded');

        $response = $request->send();
        $result = json_decode($response->getBody(true));
    }

    /**
     * Send the request to the Mashery API
     *
     * @param string $data JSON to send in request
     * @throws \InvalidArgumentException If required information is null
     * @return object API response (JSON object)
     */
    public function send($data = null)
    {
        $appId = $this->appId();
        $appSecret = $this->appSecret();
        $url = $this->getUrl();
        $client = $this->getClient();

        if ($client === null) {
            throw new \InvalidArgumentException('Client object cannot be null');
        }
        if ($appId === null) {
            throw new \InvalidArgumentException('Application ID cannot be null');
        }
        if ($appSecret === null) {
            throw new \InvalidArgumentException('Application secret cannot be null');
        }

        if ($this->getAccessToken() == null) {
            $this->authenticate();
        }

        $params = array(
            'apikey' => $apiKey,
            'sig' => $this->generateSignature()
        );
        $url = $url.'/'.$siteId.'?'.http_build_query($params);

        $request = $client->post($url);
        $request->setBody($data, 'application/json');

        $response = $request->send();
        $result = json_decode($response->getBody(true));
        return $result;
    }
}