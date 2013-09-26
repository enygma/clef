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

    /**
     * Clef application ID
     * @var string
     */
    private $appId = null;

    /**
     * Clef application secret code
     * @var string
     */
    private $appSecret = null;

    /**
     * Current user access token
     * @var string
     */
    private $accessToken = null;

    /**
     * Current user's access code
     * @var string
     */
    private $userCode = null;

    /**
     * Init the Request object
     *
     * @param string $appId Application ID [optional]
     * @param strign $appSecret Application secret [optional]
     */
    public function __construct($appId = null, $appSecret = null)
    {
        if ($appId !== null) {
            $this->setAppId($appId);
        }
        if ($appSecret !== null) {
            $this->setAppSecret($appSecret);
        }
    }

    /**
     * Set the user code
     *
     * @param string $code User code
     * @return \Clef\Request instance
     */
    public function setUserCode($code)
    {
        $this->userCode = $code;
        return $this;
    }

    /**
     * Get the current user code
     *
     * @return string User code
     */
    public function getUserCode()
    {
        return $this->userCode;
    }

    /**
     * Set the access token
     *
     * @param string $token Access token
     * @return \Clef\Request instance
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;

        // add it to the session too so it persists
        $_SESSION['accessToken'] = $token;
        return $this;
    }

    /**
     * Get the current access token value
     *
     * @return string Access token
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the Clef Application ID
     *
     * @param string $appId Application ID
     * @return \Clef\Request instance
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * Get the current Application ID
     *
     * @return string Application ID
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set the application secret
     *
     * @param string $appSecret Application secret
     * @return \Clef\Request instance
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    /**
     * Get the current application secret
     *
     * @return string Application secret
     */
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
     * @return \Clef\Request instance
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
     * @return \Clef\Request instance
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

    /**
     * Send an authentication request to the API
     *
     * @return object API JSON response decoded
     */
    public function authenticate($userCode = null)
    {
        $userCode = ($userCode == null) ? $this->getUserCode() : $userCode;
        if ($userCode === null) {
            throw new \InvalidArgumentException('User code cannot be null');
        }

        $client = $this->getClient();
        $params = array(
            'code' => $userCode,
            'app_id' => $this->getAppId(),
            'app_secret' => $this->getAppSecret()
        );

        $request = $client->post($this->getUrl().'/authorize');
        $request->setBody($params, 'application/x-www-form-urlencoded');

        $response = $request->send();
        $result = json_decode($response->getBody(true));

        if ($result !== null) {
            $this->setAccessToken($result->access_token);
        }

        return ($result == null) ? false : $result;
    }

    /**
     * Get the current (requesting) user's information
     *
     * @return mixed Either false on failure or the result on true
     */
    public function getUser()
    {
        $accessToken = $this->getAccessToken();
        $client = $this->getClient();
        $url = $this->getUrl();

        $params = array(
            'access_token' => $accessToken
        );
        $url = $url.'/info?'.http_build_query($params);

        $request = $client->get($url);
        $response = $request->send();

        try {
            $result = json_decode($response->getBody(true));
            return $result;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log out the user
     *
     * @param string $logoutToken Logout token
     * @return mixed Either false on failure or the result
     */
    public function logout($logoutToken)
    {
        $client = $this->getClient();
        $params = array(
            'logout_token' => $logoutToken,
            'app_id' => $this->getAppId(),
            'app_secret' => $this->getAppSecret()
        );

        $request = $client->post($this->getUrl().'/logout');
        $request->setBody($params, 'application/x-www-form-urlencoded');

        $response = $request->send();
        $result = json_decode($response->getBody(true));

        return ($result == null) ? false : $result;
    }

    /**
     * Send the request to the Clef API
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
        $url = $url.'?'.http_build_query($params);

        $request = $client->post($url);
        $request->setBody($data, 'application/json');

        $response = $request->send();
        $result = json_decode($response->getBody(true));
        return $result;
    }
}