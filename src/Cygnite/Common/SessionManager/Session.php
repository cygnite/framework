<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\SessionManager;

use Cygnite\Common\Security;
use Cygnite\Helpers\Config;
use Cygnite\Common\Encrypt;
use Cygnite\Foundation\Application;
use Cygnite\Common\SessionManager\Flash\FlashMessage;

/*
 * Session.
 *
 * This library is used to handle session mechanism of the cygnite framework
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

class Session implements SessionInterface
{
    public $_var = "_SESSION";

    const SESSION_PREFIX = 'Cygnite';

    private $time_reference = 'time';

    private $now;

    private $config = array();

    private $autoStart = true;

    private $initialized = false;

    private $isStarted = false;

    private $encrypt;

    private $httpOnly = true;

    private static $sessionVal;

    public static $session;

    private static $instance;

    private $crypt;

    public function __construct(Encrypt $crypt)
    {
        $this->crypt = $crypt;
        $config = array();
        $config =  Config::getConfigItems('config_items');
        $this->config = $config['config.session'];

        $this->initialize();
    }

    public function setGcProbability($value)
    {
        $value = (int)$value;

        if ($value>=0 && $value<=100) {
            ini_set('session.gc_probability', $value);
            ini_set('session.gc_divisor', '100');
        }
    }

    public function initialize()
    {
        $session_hash = 'sha512';
        // Check if hash is available
        if (in_array($session_hash, hash_algos())) {
            ini_set('session.hash_function', $session_hash);
        }// Set the has function.

        // How many bits per character of the hash.
        // The possible values are '4' (0-9, a-f), '5' (0-9, a-v), and '6' (0-9, a-z, A-Z, "-", ",").
        ini_set('session.hash_bits_per_character', 5);

        $this->setGcProbability(1);
        $this->useCookie();

        if ($this->autoStart) {
            $this->startSession();
        }

        register_shutdown_function(array($this, 'closeSession'));
    }

    private function startSession()
    {

        if ($this->isStarted === false) {
            @session_set_save_handler(
                array(
                    $this,
                    'open'
                ),
                array(
                    $this,
                    'close'
                ),
                array(
                    $this,
                    'read'
                ),
                array(
                    $this,
                    'write'
                ),
                array(
                    $this,
                    'destroy'
                ),
                array(
                    $this,
                    'gc_session'
                )
            );
        }

        if (!$this->isSessionStarted()) {
            //Set the path for session


            $keys = array('HTTP_USER_AGENT',
                'SERVER_PROTOCOL',
                'HTTP_ACCEPT_CHARSET',
                'HTTP_ACCEPT_ENCODING',
                'HTTP_ACCEPT_LANGUAGE'
            );

            $tmp = '';

            foreach ($keys as $v) {
                if (isset($_SERVER[$v])) {
                    $tmp .= $_SERVER[$v];
                }
            }

            $browser_sig = md5($tmp);
            static::$session = (isset($_SESSION) && !empty($_SESSION)) ? $_SESSION : null;

            if (empty(static::$session)) {// new session
                static::$session['log'] = md5($browser_sig);
                //  elseif (static::$session['log'] != md5($browser_sig)):

                session_start(); // create a new “clean” session
                session_destroy(); // destroy fake session
            }
            if (!empty($_SERVER['HTTP_REFERER'])) {

                $url = parse_url($_SERVER['HTTP_REFERER']);

                if ($url['host'] != $_SERVER['HTTP_HOST']) {
                    session_destroy(); // destroy fake session
                }
            }

            $path = str_replace('/', DS, APPPATH);

            if (is_dir($dir_path = APPPATH.DS.'temp'.DS.'sessions') === false) {
                if (!mkdir($dir_path, 0777)) {
                    return;
                }
            }

            $this->setSessionSavePath(CYGNITE_BASE.DS.$path.DS.'temp'.DS.'sessions'.DS);
            $this->setSessionName($this->config['cf_session_name']);
            //$this->setCookieParams($session_array);

            @session_start();

            if (!isset(static::$session['initiated'])) {
                $this->regenaratedId(true);
                static::$session['initiated'] = true;
            }

            $this->isStarted = true;
            $this->initialized=true;

            $fingerprint = 'SHIFLETT' . $_SERVER['HTTP_USER_AGENT'];
            static::$session['fingerprint'] = md5($fingerprint.$this->getsessionId());
            //$session_array = array(static::$session['fingerprint'],static::$session['initiated'], $this->getSessionName());
        }

        if ($this->getsessionId() =='') {
            $message= 'Failed to start session.';
            //trigger_error("Failed to start session.");
            if (function_exists('error_get_last')) {
                $error=error_get_last();
                if (isset($error['message'])) {
                    $message=$error['message'];
                }
            }
        }

    }

    public function closeSession()
    {
        if ($this->getsessionId() != "") {
            @session_write_close();
        }
        
        return true;
    }

    /*Session fucntions need to be edit as per save handler
    *
    */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function read($sessionId)
    {

    }

    public function write($id, $data)
    {
        return true;
    }

    /**
    * session close handler.
    * This method should be overridden if {@link use custom storage} is set true.
    * @return boolean whether session is closed successfully
    */
    public function close()
    {
        return true;
    }

    public function destroy($id)
    {
        return true;
    }

    public function gcSession($maxLifetime)
    {
        return true;
    }

    /*Session functions need to be edit as per save handler  end  */

    public function getSessionId()
    {
        if (@static::$session['initiated'] == true) {
            return session_id();
        }
    }

    public function regenaratedId($sessionId = false)
    {
        session_regenerate_id($sessionId);
    }


    private function isSessionStarted()
    {
        return ($this->getSessionId() !='') ? true : false;
    }

    private function setSessionName($name)
    {
        //ini_set('session.name','cf_session_value');
        session_name($name);
    }

    public function getSessionName()
    {
        return session_name();
    }

    private function setSessionSavePath($path)
    {
        if (is_dir($path) && is_writeable($path)) {
            ini_set('session.save_path', $path);
        } else {
            trigger_error("Unable to write session into the $path.
            Please check directory permission");
        }
    }

    private function setCookieParams($cookieValues)
    {
        // Make sure the session cookie is not accessable via javascript.
        $cookie_params =session_get_cookie_params();
        extract($cookie_params);
        extract($cookieValues);

        if (isset($this->httpOnly)) {
            session_setCookieParams($lifetime, $path, $domain, $secure, $this->httpOnly);
        } else {
            session_setCookieParams($lifetime, $path, $domain, $secure);
        }
    }

    public function getCookieParams()
    {
        return session_get_cookie_params();
    }

    public function getGcProbability()
    {
        return (int)ini_get('session.gc_probability');
    }

    private function getSessionCount()
    {
            return count(static::$session);
    }

    public function setMaxTimeout($value)
    {
        ini_set('session.gc_maxlifetime', $value);
    }

    public function getMaxTimeout()
    {
        return (int)ini_get('session.gc_maxlifetime');
    }

    // Force the session to only use cookies, not URL variables.
    private function useCookie()
    {
        ini_set('session.use_only_cookies', 1);
    }

    public function count()
    {
        return $this->getSessionCount();
    }

    /**
    * Store a session variable
    *
    * @false string $name name of the session variable
    * @false mixed $value value of the session; can be string, array, object, etc
    */
    public function save($key, $value)
    {
        /*
        if(! $this->isStarted):
        throw new ErrorException("Could not able to start session ".__FILE__);
        endif;
        */
        //$callee = debug_backtrace();
        switch ($value) {
            case is_array($value):
                static::$sessionVal = $value;
                static::$session[(string)$key]=  static::$sessionVal;
                return true;
                break;
            case is_string($value):
                if (!is_null($key)) {
                    static::$session[$key]= $this->crypt->encode($value);
                    return true;
                } else {
                    throw new \Exception('Empty key passed to '.__FUNCTION__.'()');
                }
                break;
        }


    }

    /**
     * Retrieve a session variable
     *
     * @false string $name Name of the variable you are looking for
     * @param      $key
     * @param null $defaultValue
     * @return mixed
     */
    public function get($key = null, $defaultValue = null)
    {
        if ($key == null && $defaultValue == null) {
            return static::$session;
        } else {
            switch ($key) {
                case is_array(static::$session[$key]):
                    /** @var $key TYPE_NAME */
                    return isset(static::$session[$key]) ?
                        static::$session[$key] :
                        $defaultValue;
                    break;
                case is_string(static::$session[$key]):
                    return isset(static::$session[$key]) ?
                        $this->crypt->decode(static::$session[$key]) :
                        $defaultValue;
                    break;
            }
        }
    }

    public function trash($userData = null)
    {
        if (is_string($userData)) {
            unset(static::$session[$userData]);//unset(PHPSESSID);
           // static::$session = array();
        }

        /*if (is_array($userData)) {
            foreach ($userData as $key => $val) {
                unset(static::$session[$key]);
                static::$session = array();
            }
        }*/

        if (is_null($userData)) {
            static::$session = array();
        }
       // static::$session = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie($this->getSessionName(), '', time() - 42000, '/');
        }

        //session_destroy();
        /*
         if (isset($_COOKIE[session_name()])) {
        $cookie_params = session_get_cookie_params();

        setcookie(
            session_name(),
            false,
            315554400, // strtotime('1980-01-01'),
            $cookie_falses['path'],
            $cookie_falses['domain'],
            $cookie_falses['secure']
            );
        }*/
        return true;
    }

    public function destroyAll($userData)
    {
        if (is_string($userData) && isset(static::$session[$userData])) {
            unset(static::$session[$userData]);
        }

        if (is_array($userData)) {
            foreach ($userData as $key => $val) {
                unset(static::$session[$key]);
            }
        }
        /*
        $this->clearSession();
        if($this->getSessionId() != ""):
            @session_unset();
            @session_destroy();
        endif;
        */
    }

    private function clearSession()
    {
        foreach (array_keys(static::$session) as $key) {
            unset(static::$session[$key]);
        }
    }

    public function getCurrentTime()
    {
        if (strtolower($this->time_reference) == 'gmt') {
            $now = time();
            $time = mktime(
                gmdate("H", $now),
                gmdate("i", $now),
                gmdate("s", $now),
                gmdate("m", $now),
                gmdate("d", $now),
                gmdate("Y", $now)
            );
        } else {
            $time = time();
        }

        return $time;
    }

    /*
     * Get Instance of session library
     * @access public
     * param $method instance
     * param $arguments array
     * return object
     *
     */
    public function __call($method, $arguments = array())
    {
        if ($method == 'instance') {
            return $this;
        }
    }

    /*
     * Get Instance of session library
     * @access public
     * param $method instance
     * param $arguments array
     * return object
     *
     */
    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            if (self::$instance === null) {
                self::$instance = new self(new Encrypt());
            }
            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }
    }



    public function __destruct()
    {
        session_write_close();
    }
}
