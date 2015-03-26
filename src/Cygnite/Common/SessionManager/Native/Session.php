<?php
namespace Cygnite\Common\SessionManager\Native;

use Cygnite\Common\SessionManager\Manager;
use Cygnite\Common\SessionManager\SessionInterface;
use Cygnite\Common\SessionManager\Session as SessionManager;
use Cygnite\Common\SessionManager\Exceptions\SessionNotStartedException;

class Session extends Manager implements SessionInterface
{
    /**
     * We will create instance of session wrapper and
     * validate existing session - if session is invalid, we will resets it
     *
     * @param string $sessionName
     * @param string $cacheLimiter
     */
    public function __construct($sessionName = '', $cacheLimiter = '')
    {
        /*
         |We will set session name.
         |If user doesn't provide session name we will set default name
         */
        $this->name($sessionName);

        /*
         |We will set cache limiter
         */
        $this->cacheLimiter($cacheLimiter);

        /*
         |Check if session started if not we will start new session
         |if session started already we will try
         */
        if (!$this->started()) {
            $this->startSession();
        }

        $this->storage = & $_SESSION;
    }
    /**
     * Starts session
     *
     * @throws \RuntimeException
     */
    protected function startSession()
    {
        if (@session_status() === \PHP_SESSION_ACTIVE) {
          throw new SessionNotStartedException('Session started already!');
        }

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new SessionNotStartedException(sprintf('Unable to start session, headers already sent by "%s" at line %d.', $file, $line));
        }

        /*
         | We will start session, if fails
         | we will throw exception to user
         */
        if (!session_start()) {
            throw new SessionNotStartedException('Unable to start session');
        }
    }

    /**
     * Destroy all session data and regenerates session ID
     *
     * @return $this
     */
    public function destroy()
    {
        unset($this->storage);
        $_SESSION = array();

        $sessionManager = SessionManager::getInstance();
        // set session hash
        $sessionManager->setHash(); // set session hash function
        $this->checkReferer(); // check url refferer

        /*
         |We will destroy existing session and start
         |new session for user
         */
        session_destroy();

        $this->startSession();

        $this->storage = & $_SESSION;

        return $this;
    }

    /**
     * We will check referer url from the same server or not
     * else we will destroy the session
     */
    protected function checkReferer()
    {
        if (!empty($_SERVER['HTTP_REFERER'])) {

            $url = parse_url($_SERVER['HTTP_REFERER']);

            if ($url['host'] != $_SERVER['HTTP_HOST']) {
                session_destroy(); // destroy fake session
            }
        }

    }
    /**
     * Regenerate the session ID
     *
     * @return $this
     */
    public function regenerate()
    {
        // we will regenerate session ID
        session_regenerate_id(true);

        session_write_close();

        if (isset($_SESSION)) {
            $data = $_SESSION;
            session_start();
            $_SESSION = $data;
        } else {
            session_start();
        }

        // we will store session global variable reference into storage property
        $this->storage = & $_SESSION;

        return $this;
    }

    /**
     * Check is session started, if set then return session id
     *
     * @param string $id
     *
     * @return string
     */
    public function started($id = null)
    {
        if ($id !== null) {
            session_id($id);
        }

        return session_id();
    }
    /**
     * Set or return session name
     *
     * @param string $name
     *
     * @return string
     */
    public function name($name = null)
    {
        if ($name !== null) {
            session_name($name);
        }

        return session_name();
    }
    /**
     * Set or return session cache limiter
     *
     * @param string $cacheLimiter
     *
     * @return string
     */
    public function cacheLimiter($cacheLimiter = null)
    {
        if ($cacheLimiter !== null) {
            session_cache_limiter($cacheLimiter);
        }

        return session_cache_limiter();
    }

    /**
     * We will call Manager method
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array(new Manager(), $method), array($args));
    }
}
