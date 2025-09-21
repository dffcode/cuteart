<?php
/**
 * Legacy support for older PHP versions
 * See https://packagist.org/packages/rumbletalk/rumbletalk-sdk-php
 */

namespace RumbleTalk;

use Exception;

class RumbleTalkSDK
{
    /** @var string regular expression for password validation */
    const VALIDATION_PASSWORD = '/[^,]{6,50}/';

    /** @var int chat hash (aka public id) allowed characters */
    const HASH_REGEX_PATTERN = '/^[\w*:@~!\-]{8}$/';

    /** @var integer {ENUM} different user levels */
    const UL_USER_GLOBAL = 3;
    const UL_MODERATOR = 4;
    const UL_MODERATOR_GLOBAL = 5;
    const UL_USER = 6;

    /** @var integer interval (in seconds) before token renewal is necessary */
    const TOKEN_RENEWAL_INTERVAL = 300;

    /** @var string the API root URL */
    private $host = 'https://api.rumbletalk.com/';

    /** @var integer timeout default */
    private $timeout = 30;

    /** @var string the SDK user agent */
    private $userAgent = 'rumbletalk-sdk-php-v0.5.0-legacy';

    /** @var string the app key */
    private $key;

    /** @var string the app secret */
    private $secret;

    /** @var string current access token */
    private $accessToken;

    /** @var  array additional headers (as full value) to add to the requests */
    private $additionalHeaders = array();

    /**
     * RumbleTalkSDK constructor.
     * @param string|null $key - the token key
     * @param string|null $secret - the token secret
     */
    public function __construct($key = null, $secret = null)
    {
        $this->setToken($key, $secret);
    }

    /**
     * validates a room hash structure
     * @param string $hash
     * @return bool
     */
    public static function validateHashStructure($hash)
    {
        return preg_match(self::HASH_REGEX_PATTERN, $hash);
    }

    /**
     * sets the instance's token; also removes the instance's access token
     * @param $key - the token key
     * @param $secret - the token secret
     */
    public function setToken($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->accessToken = null;
    }

    /**
     * retrieves the current instance's token
     * @return array - the instance's token
     */
    public function getToken()
    {
        return array(
            'key' => $this->key,
            'secret' => $this->secret
        );
    }

    /**
     * Extracts the expiration date from a given access token
     * @param string $token a JWT issued by the RumbleTalk server
     * @return string expiration timestamp
     */
    public static function getTokenExpiration($token)
    {
        $expiration = explode('.', $token);
        $expiration = json_decode(base64_decode($expiration[1]), true);
        return $expiration['exp'];
    }

    /**
     * Checks whether an expiration date of a token has passed.
     * @param string|integer $expiration the token or the timestamp of the token expiration
     * @param int $leeway (optional) the minimum number of seconds a token must be valid for. default: self::TOKEN_RENEWAL_INTERVAL
     * @return bool true if the token should be renewed; false otherwise
     */
    public static function renewalNeeded($expiration, $leeway = null)
    {
        if (gettype($expiration) == 'string') {
            try {
                $expiration = self::getTokenExpiration($expiration);
            } catch (Exception $ignore) {
            }
        }

        if ($leeway === null) {
            $leeway = self::TOKEN_RENEWAL_INTERVAL;
        }

        return $expiration - time() < $leeway;
    }

    /**
     * Get an access token to an account
     * This functions is for enterprise accounts and third party connections only
     * @param int $accountId the id of the account to get access to
     * @param string|null &$expiration if supplied, will be set to the token's expiration timestamp
     * @return string access token
     * @throws Exception
     */
    public function fetchAccountAccessToken($accountId = null, &$expiration = null)
    {
        $data = array(
            'key' => $this->key,
            'secret' => $this->secret
        );
        $extendRoute = '';

        if ($accountId) {
            $data['account_id'] = $accountId;
            $extendRoute = 'parent/';
        }

        $response = $this->httpRequest('POST', "{$extendRoute}token", null, $data);

        if (!@$response['status']) {
            throw new Exception('Error receiving access token: ' . wp_kses($response['message'], ''), 400);
        }
        $this->accessToken = $response['token'];

        # set the expiration date
        if ($expiration) {
            $expiration = self::getTokenExpiration($this->accessToken);
        }

        return $this->accessToken;
    }

    /**
     * Get an access token
     * This function also sets the access token for the instance; there's no need to call the 'setAccessToken' function
     * @param string|null &$expiration if supplied, will be set to the token's expiration timestamp
     * @return string access token
     * @throws Exception
     */
    public function fetchAccessToken(&$expiration = null)
    {
        return $this->fetchAccountAccessToken(null, $expiration);
    }

    /**
     * Sets the access token.
     * This functions is used when tokens are stored in your server.
     * It is required to save your tokens until they expire, because there's a limit on the number of tokens you can
     * create within a certain time
     * @param string $token - the access token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * retrieves the current instance's access token
     * @return string - the access token
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Perform a POST request to the API
     * @param string $url the API route
     * @param array $data the data to pass to the server
     * @return array the response from the server
     * @throws Exception
     */
    public function post($url, $data)
    {
        return $this->httpRequest('POST', $url, $this->accessToken, $data);
    }

    /**
     * Perform a GET request to the API
     * @param string $url the API route (including query parameters)
     * @return array the response from the server
     * @throws Exception
     */
    public function get($url)
    {
        return $this->httpRequest('GET', $url, $this->accessToken);
    }

    /**
     * Perform a PUT request to the API
     * @param string $url the API route
     * @param array $data the data to pass to the server
     * @return array the response from the server
     * @throws Exception
     */
    public function put($url, $data)
    {
        return $this->httpRequest('PUT', $url, $this->accessToken, $data);
    }

    /**
     * Perform a DELETE request to the API
     * @param string $url the API route (including query parameters)
     * @return array the response from the server
     * @throws Exception
     */
    public function delete($url)
    {
        return $this->httpRequest('DELETE', $url, $this->accessToken);
    }

    /**
     * Inner function that creates the request
     * @param string $method the method of the call
     * @param string $url the API route (including query parameters)
     * @param string|null $token a bearer token for authenticated requests
     * @param array|null $data the data to pass to the server
     * @return array|string the response from the server
     * @throws Exception
     */
    private function httpRequest($method, $url, $token = null, $data = null)
    {
        # make sure the method is in upper case for comparison
        $method = strtoupper($method);

        $this->validateMethod($method);

        # in case of a relative URL, prefix the host and encode the parts
        if (strrpos($url, 'https://') !== 0) {
            if (strpos($url, '?') !== false) {
                # detach the "path" part from the "search" part of the URL
                list($url, $search) = explode('?', $url, 2);

                # if there is a "search" part, encode it's KVPs
                if ($search) {
                    # separate each pair
                    $search = explode('&', $search);
                    foreach ($search as &$pair) {
                        # encode the key, and the value if exists.
                        $pair = explode('=', $pair);
                        $pair[0] = urlencode($pair[0]);
                        if ($pair[1]) {
                            $pair[1] = urlencode($pair[1]);
                        }
                        $pair = implode('=', $pair);
                    }
                    $search = implode('&', $search);
                }
                $url .= '?' . $search;
            }
            $url = $this->host . $url;
        }

        $headers = array(
            'User-Agent' => $this->userAgent
        );

        if ($token) {
            $headers['Authorization'] = "Bearer $token";
        }

        if (count($this->additionalHeaders) > 0) {
            $headers = array_merge($headers, $this->additionalHeaders);
        }

        $arguments = array(
            'method' => $method,
            'timeout' => $this->timeout
        );

        if (!empty($data) && $this->methodWithData($method)) {
            $data = json_encode($data);
            $arguments['body'] = $data;

            $headers['Content-Type'] = 'application/json';
            $headers['Content-Length'] = strlen($data);
        }

        $arguments['headers'] = $headers;

        $response = wp_remote_request($url, $arguments);

        $responseCode = wp_remote_retrieve_response_code($response);
        if ($responseCode !== 200) {
            return array(
                'status' => false,
                'code' => $responseCode,
                'message' => wp_remote_retrieve_response_message($response)
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        return $result ?: array('status' => false, 'message' => $body);
    }

    /**
     * validates the method
     * @param string $method the method to be validated
     * @throws Exception if the method supplied is invalid
     */
    private function validateMethod($method)
    {
        if (!in_array($method, array('POST', 'GET', 'PUT', 'DELETE'))) {
            throw new Exception('Invalid method supplied: ' . wp_kses($method, ''), 405);
        }
    }

    /**
     * checks if the method can hold data
     * @param string $method the HTTP method in question
     * @return bool true if the HTTP method can hold data
     */
    private function methodWithData($method)
    {
        return in_array($method, array('POST', 'PUT'));
    }

    /**
     * Validates an email address
     * @param string $email the email address to validate
     * @return bool true if the email is valid, false otherwise
     */
    public function validateEmail(&$email)
    {
        if (!is_string($email)) {
            return false;
        }
        $email = trim($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates that a password meets our demands
     * @param string $password the password to validate
     * @return bool true if the password is valid, false otherwise
     */
    public function validatePassword($password)
    {
        return is_string($password) && preg_match(self::VALIDATION_PASSWORD, $password);
    }

    /**
     * Adds the given headers to the requests
     * @param array $headers - the given headers in full format; e.g. ['Location: /', 'x-example: value']
     * @return bool - true on success, false on failure
     */
    public function setAdditionalHeaders($headers)
    {
        if (is_array($headers)) {
            $this->additionalHeaders = $headers;

            return true;
        }

        return false;
    }
}
