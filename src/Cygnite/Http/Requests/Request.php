<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Http\Requests;

use Cygnite\Http\Header;
use Cygnite\Foundation\Collection;

/**
 * Class Request
 *
 * @package Cygnite\Http\Requests
 * @reference https://github.com/symfony/http-foundation/blob/master/Request.php
 */

class Request
{
    // Array of valid methods
    private static $validMethods = [
        RequestMethods::DELETE,
        RequestMethods::GET,
        RequestMethods::POST,
        RequestMethods::PUT,
        RequestMethods::HEAD,
        RequestMethods::TRACE,
        RequestMethods::PURGE,
        RequestMethods::CONNECT,
        RequestMethods::PATCH,
        RequestMethods::OPTIONS
    ];
    // The list of trusted proxies
    private static $trustedProxies = [];

    //The list of trusted headers
    private static $trustedHeaderNames = [
        RequestHeaderConstants::FORWARDED => "FORWARDED",
        RequestHeaderConstants::CLIENT_IP => "X_FORWARDED_FOR",
        RequestHeaderConstants::CLIENT_HOST => "X_FORWARDED_HOST",
        RequestHeaderConstants::CLIENT_PORT => "X_FORWARDED_PORT",
        RequestHeaderConstants::CLIENT_PROTO => "X_FORWARDED_PROTO"
    ];

    //The method used in the request
    private $method = "";

    // @var array The client IP addresses
    private $clientIps = [];

    private $query;

    private $post;

    private $put;

    private $patch;

    private $delete;

    private $header;

    private $server;

    private $files;

    private $env;

    private $cookie;

    private $path = "";

    private $refererUrl = "";

    private $content;

    /**
     * @var base url
     */
    public $currentUrl;

    public $base;

    protected static $httpMethodParameterOverride = false;

    /**
     * Constructor of Request class
     *
     * @param array $query
     * @param array $post
     * @param array $cookie
     * @param array $server
     * @param array $files
     * @param array $env
     * @param null $content
     */
    public function __construct(array $query, array $post, array $cookie, array $server, array $files, array $env, $content = null)
    {
        $this->initialize($query, $post, $cookie, $server, $files, $env, $content);
    }

    /**
     * Initialize parameters for current request
     *
     * @param array $query
     * @param array $post
     * @param array $cookie
     * @param array $server
     * @param array $files
     * @param array $env
     * @param null $content
     */
    public function initialize(array $query, array $post, array $cookie, array $server, array $files, array $env, $content = null)
    {
        $this->query = new Collection($query);
        $this->post = new Collection($post);
        $this->put = new Collection([]);
        $this->patch = new Collection([]);
        $this->delete = new Collection([]);
        $this->env = new Collection($env);
        $this->header = new Header($server);
        $this->server = new Collection($server);
        $this->cookie = new Collection($cookie);

        $this->files = new Files($files);
        $this->content = $content;
        $this->method = null;
        $this->setClientIPs();
        $this->setPath();
        $this->setUnsupportedMethodsIfExists();
    }

    /**
     * Create a new request using PHP super global variables
     *
     * @param array $query
     * @param array $post
     * @param array $cookie
     * @param array $server
     * @param array $files
     * @param array $env
     * @param null $content
     * @return static
     */
    public static function createFromGlobals(
        array $query = null,
        array $post = null,
        array $cookie = null,
        array $server = null,
        array $files = null,
        array $env = null,
        $content = null
    ) {
        $query = isset($query) ? $query : $_GET;
        $post = isset($post) ? $post : $_POST;
        $cookie = isset($cookie) ? $cookie : $_COOKIE;
        $server = isset($server) ? $server : $_SERVER;
        $files = isset($files) ? $files : $_FILES;
        $env = isset($env) ? $env : $_ENV;

        $server = self::setServerParam($server, [
            'CONTENT_LENGTH' => 'HTTP_CONTENT_LENGTH',
            'CONTENT_TYPE' => 'HTTP_CONTENT_TYPE'
        ]);

        return new static($query, $post, $cookie, $server, $files, $env, $content);
    }

    /**
     * Set content parameter if exists in server array
     *
     * @param $server
     * @param $arr
     * @return mixed
     */
    private static function setServerParam($server, $arr)
    {
        foreach ($arr as $key => $value) {
            if (array_key_exists($value, $server)) {
                $server[$key] = $server[$value];
            }
        }

        return $server;
    }

    /**
     * Create a request based on the uri given, populate
     * server information.
     *
     * @param $url
     * @param $method
     * @param array $parameters
     * @param array $cookies
     * @param array $server
     * @param array $files
     * @param array $env
     * @param null $rawBody
     * @return static
     */
    public static function create(
        $url,
        $method = RequestMethods::GET,
        array $parameters = [],
        array $cookies = [],
        array $server = [],
        array $files = [],
        array $env = [],
        $rawBody = null
    ) {
        // Define some basic server vars, but override them with the input on collision
        $server = array_replace([
                "HTTP_ACCEPT" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "HTTP_HOST" => "localhost",
                "REMOTE_ADDR" => "127.0.01",
                "SCRIPT_FILENAME" => "",
                "SCRIPT_NAME" => "",
                "SERVER_NAME" => "localhost",
                "SERVER_PORT" => 80,
                "SERVER_PROTOCOL" => "HTTP/1.1",
                'HTTP_USER_AGENT' => 'Cygnite/2.X',
                'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                'REQUEST_TIME' => time()
            ], $server);

        $query = [];
        $post = [];

        // Set the content type for unsupported HTTP methods
        switch (strtoupper($method)) {
            case RequestMethods::GET:
                $query = $parameters;
                break;
            case RequestMethods::PATCH:
            case RequestMethods::PUT:
            case RequestMethods::DELETE:
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
            // no break
            case RequestMethods::POST:
                $post = $parameters;
                break;
        }

        $server["REQUEST_METHOD"] = strtoupper($method);
        $urlParts = parse_url($url);

        $server = static::setServerData($urlParts, [
            'host' => 'HTTP_HOST',
            'path' => 'REQUEST_URI',
            'user' => 'PHP_AUTH_USER',
            'pass' => 'PHP_AUTH_PW',
        ], $server);

        if (isset($urlParts["scheme"])) {
            if ($urlParts["scheme"] == "https") {
                $server["HTTPS"] = "on";
                $server["SERVER_PORT"] = 443;
            } else {
                unset($server["HTTPS"]);
                $server["SERVER_PORT"] = 80;
            }
        }

        if (isset($urlParts["query"])) {
            parse_str(html_entity_decode($urlParts["query"]), $queryFromUrl);
            $query = array_replace($queryFromUrl, $query);
        }

        if (!isset($urlParts['path'])) {
            $urlParts['path'] = '/';
        }

        $qs = http_build_query($query, "", "&");
        $server["QUERY_STRING"] = $qs;
        $server["REQUEST_URI"] = $urlParts['path'].(count($query) > 0 ? '?'.$qs : '');

        if (isset($urlParts["port"])) {
            $server = static::setServerData($urlParts, [
                'port' => 'SERVER_PORT',
                ":{$urlParts["port"]}" => 'HTTP_HOST',
            ], $server);
        }

        return new static($query, $post, $cookies, $server, $files, $env, $rawBody);
    }

    /**
     * Set param to server variable is exists in url parts
     *
     * @param $parts
     * @param $params
     * @param $server
     * @return mixed
     */
    private static function setServerData($parts, $params, $server)
    {
        foreach ($params as $key => $value) {
            if (isset($parts[$key])) {
                $server[$value] = $parts[$key];
            }
        }

        return $server;
    }

    /**
     * Set the path of the current request. It doesn't include the query
     * string if no input specified and automatically set path using the header
     *
     * @param null $path
     * @return bool
     */
    public function setPath($path = null)
    {
        if ($path === null) {
            $uri = $this->server->get("REQUEST_URI");

            if (!empty($uri)) {
                $uriParts = explode("?", $uri);
                $this->path = $uriParts[0];

                return true;
            }

            // Default to a slash
            $this->path = "/";

            return true;
        }

        $this->path = $path;

        return true;
    }

    /**
     * Sets a trusted header name
     *
     * @param string $name
     * @param mixed $value
     */
    public static function setTrustedHeaderName($name, $value)
    {
        self::$trustedHeaderNames[$name] = $value;
    }

    /**
     * Sets the list of trusted proxy Ips
     *
     * @param array|string $trustedProxies
     */
    public static function setTrustedProxies($trustedProxies)
    {
        self::$trustedProxies = (array)$trustedProxies;
    }

    /**
     * The method is to activate the HTTP method override
     *
     */
    public static function activateHttpMethodOverride()
    {
        self::$httpMethodParameterOverride = true;
    }

    /**
     * Sets the client IP addresses
     */
    private function setClientIPs()
    {
        if ($this->isUsingTrustedProxy()) {
            $this->clientIps = [$this->server->get("REMOTE_ADDR")];
        }
        $clientIps = [];

        if ($this->header->has(self::$trustedHeaderNames[RequestHeaderConstants::FORWARDED])) {
            $header = $this->header->get(self::$trustedHeaderNames[RequestHeaderConstants::FORWARDED]);
            preg_match_all("/for=(?:\"?\[?)([a-z0-9:\.\-\/_]*)/", $header, $matches);
            $clientIps = $matches[1];
        } elseif ($this->header->has(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_IP])) {
            $clientIps = explode(",", $this->header->get(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_IP]));
            $clientIps = array_map("trim", $clientIps);
        }

        $clientIps[] = $this->server->get("REMOTE_ADDR");
        $fallbackClientIps = [$clientIps[0]];

        foreach ($clientIps as $index => $clientIp) {
            // Check for valid IPs
            if (!filter_var($clientIp, FILTER_VALIDATE_IP)) {
                unset($clientIps[$index]);

                continue;
            }

            // Don't allow trusted proxies
            if (in_array($clientIp, self::$trustedProxies)) {
                unset($clientIps[$index]);
            }
        }

        $this->clientIps = (count($clientIps) == 0) ? $fallbackClientIps : array_reverse($clientIps);
    }

    /**
     * Sets PUT/PATCH/DELETE collections, if they exist
     */
    private function setUnsupportedMethodsIfExists()
    {
        if (!function_exists('mb_strpos')) {
            throw new \Exception('You must have php-mbstring extension installed in the server.');
        }
    
        /**
         * If the content is not passed from FORM then we will simply return corresponding
         * GLOBAL variable raw data
         */
        if (
            \mb_strpos($this->header->get("CONTENT_TYPE"), "application/x-www-form-urlencoded") === 0 &&
            in_array($this->getMethod(), [RequestMethods::PUT, RequestMethods::PATCH, RequestMethods::DELETE])
        ) {
            parse_str($this->getRawBody(), $array);

            switch ($this->getMethod()) {
                case RequestMethods::PUT:
                    $this->put->exchangeArray($array);
                    break;
                case RequestMethods::PATCH:
                    $this->patch->exchangeArray($array);
                    break;
                case RequestMethods::DELETE:
                    $this->delete->exchangeArray($array);
                    break;
            }
        }
    }

    /**
     * Sets the referred by URL
     *
     * @param string $url The url referred from
     */
    public function setUrlReferredFrom($url)
    {
        $this->refererUrl = $url;
    }

    /**
     * Sets the request method.
     *
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);

        return $this;
    }

    /**
     * Call methods if exists
     *
     * Example:
     * $request->query->get();
     * $request->server->get();
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        $func = 'get'.ucfirst($name);
        if (method_exists($this, $func)) {
            return $this->{$func}();
        }
    }

    /**
     * @return string
     */
    public function getClientIPAddress()
    {
        return $this->clientIps[0];
    }

    /**
     * @return Collection
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @return Collection
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * @return Collection
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @return Files
     */
    public function getFile()
    {
        return $this->files;
    }

    /**
     * Get the full URL of the current request
     *
     * @return string
     */
    public function getFullUrl()
    {
        $isSecure = $this->isSecure();
        $srvProtocol = strtolower($this->server->get('SERVER_PROTOCOL'));
        $protocol = substr($srvProtocol, 0, strpos($srvProtocol, '/')) . (($isSecure) ? 's' : '');
        $port = $this->getPort();
        $port = ((!$isSecure && $port != '80') || ($isSecure && $port != '443')) ? ":$port" : '';

        return $protocol . '://' . $this->getHost() . $port . $this->server->get("REQUEST_URI");
    }

    /**
     * Gets the host name
     *
     * @return string
     * @throws \UnexpectedValueException Thrown if the host name invalid
     */
    public function getHost()
    {
        if ($this->isUsingTrustedProxy() && $this->header->has(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_HOST])) {
            $hosts = explode(",", $this->header->get(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_HOST]));
            $host = trim(end($hosts));
        } else {
            $host = $this->header->get("X_FORWARDED_FOR");
        }

        $host = $this->getHostIfNull($host);

        // Remove the port number
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        // Check for forbidden characters
        if (!empty($host) && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
            throw new \UnexpectedValueException("Invalid host $host");
        }

        return $host;
    }

    /**
     * Check if host variable is null then search
     * host name from different global variables
     *
     * @param $host
     * @return mixed
     */
    private function getHostIfNull($host)
    {
        if ($host === null) {
            $host = $this->header->get("HOST");
        }

        if ($host === null) {
            $host = $this->server->get("SERVER_NAME");
        }

        if ($host === null) {
            // Return an empty string by default so we can do string operations on it later
            $host = $this->server->get("SERVER_ADDR", "");
        }

        return $host;
    }

    /**
     * Returns the input from either GET, POST, PUT etc. data
     *
     * @param $name
     * @param null $default
     * @return null
     */
    public function input($name, $default = null)
    {
        if ($this->isJson()) {
            $json = $this->getJsonBody();
            return array_key_exists($name, $json) ? $json[$name] : $default;
        }

        $value = null;
        switch ($this->getMethod()) {
            case RequestMethods::GET:
                return $this->query->get($name, $default);
            case RequestMethods::POST:
                $value = $this->post->get($name, $default);
                break;
            case RequestMethods::PUT:
                $value = $this->put->get($name, $default);
                break;
            case RequestMethods::PATCH:
                $value = $this->patch->get($name, $default);
                break;
            case RequestMethods::DELETE:
                $value = $this->delete->get($name, $default);
                break;
        }

        if ($value === null) {
            // if value is null Try falling back to query
            $value = $this->query->get($name, $default);
        }

        return $value;
    }

    /**
     * Gets the raw body as a JSON array
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getJsonBody()
    {
        $json = json_decode($this->getRawBody(), true);

        if ($json === null) {
            throw new \RuntimeException("Response body unable to decode as JSON");
        }

        return $json;
    }

    /**
     * Returns current script name.
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * Get the auth user
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->server->get("PHP_AUTH_USER");
    }

    /**
     * Get the auth password
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->server->get("PHP_AUTH_PW");
    }

    /**
     * Gets the user info.
     *
     * @return string
     */
    public function getUserInfo()
    {
        $userInfo = $this->getUser();

        $pass = $this->getPassword();
        if ('' != $pass) {
            $userInfo .= ":$pass";
        }

        return $userInfo;
    }

    /**
     * @return Collection
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return Collection
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return Collection
     */
    public function getPut()
    {
        return $this->put;
    }

    /**
     * @return Collection
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Collection
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Returns header object
     *
     * @return Header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Get the raw body
     *
     * @return string
     */
    public function getRawBody()
    {
        if ($this->content === null) {
            $this->content = file_get_contents("php://input");
        }

        return $this->content;
    }

    /**
     * Get the port number
     *
     * @return int The port number
     */
    public function getPort()
    {
        if ($this->isUsingTrustedProxy()) {
            if ($this->server->has(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_PORT])) {
                return (int)$this->server->get(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_PORT]);
            } elseif ($this->server->get(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_PROTO]) === "https") {
                return 443;
            }
        }

        return (int)$this->server->get("SERVER_PORT");
    }

    /**
     * @return bool
     */
    public static function getHttpMethodOverride()
    {
        return self::$httpMethodParameterOverride;
    }

    /**
     * @return null|string
     */
    public function getMethod()
    {
        $method = $this->method;

        if (null === $method) {
            $method = $this->server->get('REQUEST_METHOD', RequestMethods::GET);

            if ($method == RequestMethods::POST) {
                if ($overrideMethod = $this->header->get('X-HTTP-METHOD-OVERRIDE') !== null) {
                    $method = $overrideMethod;
                } elseif (self::$httpMethodParameterOverride) {
                    $method = $this->post->get('_method', $this->query->get('_method', $method));
                }
            }
        }

        return $this->method = $this->filterMethod($method);
    }

    /**
     * @param $method
     * @return null|string
     * @throws \InvalidArgumentException
     */
    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported HTTP method; must be a string, received %s',
                    is_object($method) ? get_class($method) : gettype($method)
                )
            );
        }

        $method = strtoupper($method);

        if (!in_array($method, self::$validMethods)) {
            throw new \InvalidArgumentException(sprintf('Invalid HTTP method "%s" provided', $method));
        }

        return $method;
    }

    /**
     * Check whether the request is AJAX request or not.
     * Return true or false
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->header->get("X_REQUESTED_WITH") == "XMLHttpRequest";
    }

    /**
     * Check whether request body is JSON or not
     *
     * @return bool
     */
    public function isJson()
    {
        return preg_match("/application\/json/i", $this->header->get("CONTENT_TYPE")) === true;
    }

    /**
     * Check if the given path matches with input path
     *
     * @param $path
     * @param bool $isRegex
     * @return bool
     */
    public function isPath($path, $isRegex = false)
    {
        if ($isRegex) {
            return preg_match("#^" . $path . "$#", $this->path) === 1;
        } else {
            return ($this->path == $path);
        }
    }

    /**
     * The referrer URL, if set otherwise return the referrer header
     *
     * @param bool $fallBackToReferer
     * @return string
     */
    public function getReferrerUrl($fallBackToReferer = true)
    {
        if (!empty($this->refererUrl)) {
            return $this->refererUrl;
        }

        if ($fallBackToReferer) {
            return $this->header->get("REFERER", "");
        }

        return "";
    }

    /**
     * Get the base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        // Current Request URI
        $this->currentUrl = $this->server->get('REQUEST_URI');

        // Remove rewrite base path (= allows one to run the router in a sub folder)
        $basePath = implode('/', array_slice(explode('/', $this->server->get('SCRIPT_NAME')), 0, -1)) . '/';

        return $basePath;
    }

    /**
     * Define the current relative URI
     *
     * @return string
     */
    public function getCurrentUri()
    {
        $basePath = $this->getBaseUrl();
        $uri = $this->currentUrl;

        $this->base = $basePath;
        $uri = substr($uri, strlen($basePath));

        // Don't take query params into account on the URL
        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * Check whether the request is through HTTPS or not
     *
     * @return bool
     */
    public function isSecure()
    {
        if ($this->isUsingTrustedProxy() && $this->server->has(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_PROTO])) {
            $protocolString = $this->server->get(self::$trustedHeaderNames[RequestHeaderConstants::CLIENT_PROTO]);
            $protocols = explode(",", $protocolString);

            return count($protocols) > 0 && in_array(strtolower($protocols[0]), ["https", "ssl", "on"]);
        }

        return $this->server->has("HTTPS") && strtolower($this->server->get("HTTPS")) !== "off";
    }

    /**
     * Check if current url matches the given url
     *
     * @param $url
     * @param bool $isRegex
     * @return bool
     */
    public function isUrl($url, $isRegex = false)
    {
        if ($isRegex) {
            return preg_match("#^" . $url . "$#", $this->getFullUrl()) === 1;
        }

        return $this->getFullUrl() == $url;
    }

    /**
     * Check whether we're using a trusted proxy
     *
     * @return bool
     */
    private function isUsingTrustedProxy()
    {
        return in_array($this->server->get("REMOTE_ADDR"), self::$trustedProxies);
    }

    /**
     * Clones the current request objects
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->post = clone $this->post;
        $this->put = clone $this->put;
        $this->patch = clone $this->patch;
        $this->delete = clone $this->delete;
        $this->cookie = clone $this->cookie;
        $this->server = clone $this->server;
        $this->header = clone $this->header;
        $this->files = clone $this->files;
        $this->env = clone $this->env;
    }
}
