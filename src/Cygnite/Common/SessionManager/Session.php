<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Common\SessionManager;

use Cygnite\Helpers\Config;

/*
 * Session Manager
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Session
{
    /**
     * Available drivers for session storage
     * @var array
     */
    protected $drivers = array(
        'native' => 'Native\\Session',
        'database' => 'Database\\Session',
        'redis' => 'Memory\\Redis',
    );

    protected $config = array();

    // Default session name
    public $name = 'cygnite-session';

    public $cacheLimiter;

    public static $instance;

    /**
     * Session Constructor
     */
    public function __construct()
    {
        $config = array();
        $config =  Config::getConfigItems('config.items');

        /*
         | We will set session configuration into config property
         | Based on user defined configuration we will load the session
         | driver
         */
        $this->config = $config['config.session'];
    }

    /**
     * @param $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : null;
    }

    /**
     * @param $name
     * @return void
     */
    public function setCacheLimiter($name)
    {
        $this->cacheLimiter = $name;
    }

    /**
     * @return null
     */
    public function getCacheLimiter()
    {
        return isset($this->cacheLimiter) ? $this->cacheLimiter : null;
    }

    /**
     * We will call all functions statically
     * Session::set();
     * Session::get();
     * Session::has();
     * Session::delete();
     * Session::destroy();
     *
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $arguments['method'] = $method;
        self::$instance = new Static;
        return call_user_func_array(array(self::$instance, 'factory'), array($arguments));
    }

    /**
     * Factory method. We will get the session driver and call methods
     *
     * @param $args
     * @return mixed
     */
    protected function factory($args)
    {
        $method = $class = null;
        $class = __NAMESPACE__.'\\'.$this->drivers[$this->config['driver']];
        $method = $args['method'];
        unset($args['method']);

        $name = $this->getName();
        if ($name != 'cygnite-session' && !is_null($this->cacheLimiter())) {
            $session = new $class($name, $this->cacheLimiter());
        }

        $session = new $class($this->name);

        return call_user_func_array(array($session, $method), $args);
    }

    /**
     * Get the instance of session manager
     * @return null
     */
    public static function getInstance()
    {
        return is_object(self::$instance) ? self::$instance : null;
    }

    /**
     * We will set hashing algorithm for session
     */
    public function setHash()
    {
        // Hash algorithm to use for the session. (use hash_algos() to get a list of available hashes.)
        $session_hash = 'sha512';

        // Check if hash is available
        if (in_array($session_hash, hash_algos())) {
            // Set the has function.
            ini_set('session.hash_function', $session_hash);
        }
        // How many bits per character of the hash.
        // The possible values are '4' (0-9, a-f), '5' (0-9, a-v), and '6' (0-9, a-z, A-Z, "-", ",").
        ini_set('session.hash_bits_per_character', 5);
    }

    // Force the session to only use cookies, not URL variables.
    public function useOnlyCookie()
    {
        // Force the session to only use cookies, not URL variables.
        ini_set('session.use_only_cookies', 1);
    }

    /**
     * @param $secure
     * @param $httpOnly
     */
    public function setCookieParams($secure, $httpOnly)
    {
        // Get session cookie parameters
        $cookieParams = session_get_cookie_params();
        // Set the parameters
        session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httpOnly);
    }
}
