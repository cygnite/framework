<?php
namespace Cygnite\Http;

use Cygnite\Http\Requests\RequestMethods;

/**
 * Class CsrfValidator
 *
 * @package Cygnite\Http
 */
class CsrfValidator
{
    protected $token;

    protected $spoofedMethods = [RequestMethods::DELETE, RequestMethods::PATCH, RequestMethods::PUT];

    protected $session;

    protected $randomToken;

    public static $instance;

    /**
     * Constructor of CsrfValidator
     *
     * We will initialize session and set token into it
     *
     * @param Session $session
     * @param null    $randomNum
     */
    public function __construct($session, $randomNum = null)
    {
        $this->storage = $session;
        (is_null($randomNum)) ? $this->setRandomToken() : $this->setRandomToken($randomNum);

        $this->setTokenIntoSession();
    }


    /**
     * @param $session
     * @param null $random
     * @param callable $callback
     * @return static
     */
    public static function make($session, $random = null, \Closure $callback = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($session, $random);
        }

        if ($callback instanceof \Closure) {
            return $callback(self::$instance);
        }

        return self::$instance;
    }


    /**
     * @param $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Set unique token for the form
     * and store into session
     *
     */
    public function setTokenIntoSession()
    {
        $token = $this->getRandomToken();

        if (empty($this->storage->csrf_tokens)) {
            $this->storage->csrf_tokens = [];
        }

        $this->storage->csrf_tokens = [trim($token) => true];

        $this->token = $token;
    }

    /**
     * @param null $value
     * @return $this
     */
    public function setRandomToken($value = null)
    {
        if (is_null($value)) {
            $this->randomToken = md5(uniqid(rand(), true));

            return $this;
        }

        $this->randomToken = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getRandomToken()
    {
        return $this->randomToken;
    }

    /**
     * Get the csrf token
     *
     * Alias function csrf_token();
     *
     * @return mixed
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * We will validate if requested method is other then
     * spoofed methods for validating csrf token
     *
     * @return bool
     */
    public function isValidatedRequest()
    {
        return in_array($_SERVER['REQUEST_METHOD'], $this->spoofedMethods);
    }

    /**
     * @param bool $throw
     * @return bool
     */
    public function validateRequest($throw = false)
    {
        if (!$this->isValidatedRequest()) {
            return true;
        }
    }

    /**
     * Validate token and return boolean value
     * if matched with input
     *
     * @param $token
     * @return bool
     */
    public function validateToken($token)
    {
        /*
         | Return false if given token is not string
         */
        if (!is_string($token)) {
            return false;
        }

        if (isset($this->storage->csrf_tokens[$token])) {
            $this->storage->csrf_tokens = [trim($token) => null];

            return true;
        }

        return false;
    }

    /**
     * We will kill script
     */
    protected function killScript()
    {
        header('HTTP/1.0 400 Bad Request');
        exit();
    }

    /**
     * Validate csrf token with stored token, if matches
     * we will return true else false;
     *
     * alias method: validate_token($token);
     *
     * @param $token
     * @return bool
     */
    public function validateRequestToken($token)
    {
        return is_string($token) && $this->validateToken($token);
    }
}
