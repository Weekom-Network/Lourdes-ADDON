<?php

require_once('OAuth.php');

/**
 * Twitter OAuth class
 */
class TwitterOAuth
{
    /* Contains the last HTTP status code returned. */
    public $http_code;
    /* Contains the last API call. */
    public $url;
    /* Set up the API root URL. */
    public $host = "https://api.twitter.com/1.1/";
    /* Set timeout default. */
    public $timeout = 30;
    /* Set connect timeout. */
    public $connecttimeout = 30;
    /* Verify SSL Cert. */
    public $ssl_verifypeer = FALSE;
    /* Respons format. */
    public $format = 'json';
    /* Decode returned json data. */
    public $decode_json = TRUE;
    /* Contains the last HTTP headers returned. */
    public $http_info;
    /* Set the useragnet. */
    public $useragent = 'TwitterOAuth v0.2.0-beta2';
    /* Immediately retry the API call if the response was not successful. */
    //public $retry = TRUE;


    /**
     * Set API URLS
     */
    function accessTokenURL()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    /**
     * @return string
     */
    function authenticateURL()
    {
        return 'https://api.twitter.com/oauth/authenticate';
    }

    /**
     * @return string
     */
    function authorizeURL()
    {
        return 'https://api.twitter.com/oauth/authorize';
    }

    /**
     * @return string
     */
    function requestTokenURL()
    {
        return 'https://api.twitter.com/oauth/request_token';
    }

    /**
     * construct TwitterOAuth object
     *
     * @param      $consumer_key
     * @param      $consumer_secret
     * @param null $oauth_token
     * @param null $oauth_token_secret
     */
    function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL)
    {
        $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
        if (!empty($oauth_token) && !empty($oauth_token_secret)) {
            $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
        } else {
            $this->token = NULL;
        }
    }


    /**
     * Get a request_token from Twitter
     *
     * @param $oauth_callback
     *
     * @return array
     */
    function getRequestToken($oauth_callback)
    {
        $parameters = [];
        $parameters['oauth_callback'] = $oauth_callback;
        $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    /**
     * Get the authorize URL
     *
     * @param      $token
     * @param bool $sign_in_with_twitter
     *
     * @return string
     */
    function getAuthorizeURL($token, $sign_in_with_twitter = TRUE)
    {
        if (is_array($token)) {
            $token = $token['oauth_token'];
        }
        if (empty($sign_in_with_twitter)) {
            return $this->authorizeURL() . "?oauth_token={$token}";
        } else {
            return $this->authenticateURL() . "?oauth_token={$token}";
        }
    }

    /**
     * Exchange request token and secret for an access token and
     * secret, to sign API calls.
     *
     * @param $oauth_verifier
     *
     * @return array ("oauth_token" => "the-access-token",
     *                "oauth_token_secret" => "the-access-secret",
     * "user_id" => "9436992",
     * "screen_name" => "abraham")
     */
    function getAccessToken($oauth_verifier)
    {
        $parameters = [];
        $parameters['oauth_verifier'] = $oauth_verifier;
        $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    /**
     * One time exchange of username and password for access token and secret.
     *
     * @param $username
     * @param $password
     *
     * @return array ("oauth_token" => "the-access-token",
     *                "oauth_token_secret" => "the-access-secret",
     * "user_id" => "9436992",
     * "screen_name" => "abraham",
     * "x_auth_expires" => "0")
     */
    function getXAuthToken($username, $password)
    {
        $parameters = [];
        $parameters['x_auth_username'] = $username;
        $parameters['x_auth_password'] = $password;
        $parameters['x_auth_mode'] = 'client_auth';
        $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    /**
     * GET wrapper for oAuthRequest.
     *
     * @param       $url
     * @param array $parameters
     *
     * @return mixed
     */
    function get($url, $parameters = [])
    {
        $response = $this->oAuthRequest($url, 'GET', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * POST wrapper for oAuthRequest.
     *
     * @param       $url
     * @param array $parameters
     *
     * @return mixed
     */
    function post($url, $parameters = [])
    {
        $response = $this->oAuthRequest($url, 'POST', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode((string)$response);
        }
        return $response;
    }

    /**
     * DELETE wrapper for oAuthReqeust.
     *
     * @param       $url
     * @param array $parameters
     *
     * @return mixed
     */
    function delete($url, $parameters = [])
    {
        $response = $this->oAuthRequest($url, 'DELETE', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * Format and sign an OAuth / API request
     *
     * @param $url
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    function oAuthRequest($url, $method, $parameters)
    {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
            $url = "{$this->host}{$url}.{$this->format}";
        }
        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
        $request->sign_request($this->sha1_method, $this->consumer, $this->token);

        switch ($method) {
            case 'GET':
                return $this->http($request->to_url(), 'GET');
            default:
                return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
        }
    }

    /**
     * Make an HTTP request
     *
     * @param      $url
     * @param      $method
     * @param null $postfields
     *
     * @return mixed
     */
    function http($url, $method, $postfields = NULL)
    {
        $this->http_info = [];
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, [$this, 'getHeader']);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);
        return $response;
    }

    /**
     * Get the header info to store.
     * @param $header
     * @return int
     */
    function getHeader($header): int
    {
        return strlen((string)$header);
    }
}