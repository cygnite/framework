<?php
namespace Cygnite\Common\SessionManager\Database;

use Cygnite\Common\Encrypt;
use Cygnite\Common\SessionManager\PacketInterface;
use Cygnite\Common\SessionManager\Session as SessionManager;
use Cygnite\Database\Cyrus\ActiveRecord;
use Cygnite\Database\Schema;
use Cygnite\Helpers\Config;

class Session extends ActiveRecord implements PacketInterface
{
    protected $tableName = 'cf_sessions';
    protected $storage;
    protected $database;
    private $config = [];
    private $wrapper;
    private $name;

    public function __construct($name = null, $cacheLimiter = null, $wrapperInstance = null)
    {
        $this->name = $name;
        /*
         | Override native session handler
         */
        $this->sessionSaveHandler();

        /*
         | Get user configuration
         */
        $this->config = Config::get('config.session');

        /*
         | Set Database and Table for storing
         | session into database
         */
        $this->database($this->config['database_name']);
        $this->table($this->config['table']);
        $this->setWrapperInstance($wrapperInstance);

        /*
        |Check if session started if not we will start new session
        |if session started already we will try
        */
        if (!session_id()) {
            $this->start();
        }

        $this->storage = & $_SESSION;
        // This line prevents unexpected effects when using objects as save handlers.
        register_shutdown_function('session_write_close');
    }

    /**
     * Set session save handler to override native handler
     */
    public function sessionSaveHandler()
    {
        @session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc_session']
        );
    }

    /**
     * @param null $database
     */
    public function database($database = null)
    {
        $this->database = $database;
    }

    /**
     * @param null $table
     */
    public function table($table = null)
    {
        $this->tableName = $table;
    }

    public function start()
    {
        $this->setSessionConfig();

        // Change the session name
        $this->name($this->name);
        // Now we cat start the session
        session_start();
        // This line regenerates the session and delete the old one.
        // It also generates a new encryption key in the database.
        session_regenerate_id(true);
    }

    public function setSessionConfig()
    {
        // Make sure the session cookie is not accessible via javascript.
        $httpOnly = $this->config['httponly'];
        $secure = $this->config['secure'];

        $sessionManager = $this->getWrapper();
        $sessionManager->setHash(); // set session hash
        $sessionManager->useOnlyCookie(); // use cookie

        $sessionManager->setCookieParams($secure, $httpOnly);
    }

    /**
     * Get the instance of session manager
     *
     * @return null
     */
    public function getWrapper()
    {
        return isset($this->wrapper) ? $this->wrapper : null;
    }

    /**
     * Set the name of session
     *
     * @param null $name
     * @return string
     */
    public function name($name = null)
    {
        if ($name !== null) {
            session_name($name);
        }

        return session_name();
    }

    public function setWrapperInstance($instance)
    {
        $this->wrapper = $instance;
    }

    /**
     * @return null
     */
    public function getDatabaseName()
    {
        return isset($this->database) ? $this->database : null;
    }

    /**
     * Open an connection
     *
     * @return bool
     */
    public function open()
    {
        $this->createTableIfNotExists();

        return true;
    }

    /**
     * We will create session schema if not exists
     */
    public function createTableIfNotExists()
    {
        Schema::instance(
            $this,
            function ($table) {
                $table->tableName = $this->getTable();

                /**
                | Check if table already exists
                | if not we will create an table to store session info
                 */
                if (!$table->hasTable()->run()) {
                    $table->create(
                        [
                            [
                                'column' => 'id',
                                'type' => 'int',
                                'length' => 11,
                                'increment' => true,
                                'key' => 'primary'
                            ],
                            ['column' => 'access', 'type' => 'int', 'length' => 11],
                            ['column' => 'data', 'type' => 'text'],
                            ['column' => 'session_id', 'type' => 'string', 'length' => 128],
                        ],
                        'InnoDB',
                        'latin1'
                    )->run();
                }
            }
        );
    }

    /**
     * @return null|string
     */
    public function getTable()
    {
        return isset($this->tableName) ? $this->tableName : null;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function write($id, $data)
    {
        $access = time();
        $this->session_id = (string)$id;
        $this->access = $access;
        $this->data = $data;
        $this->save();

        return true;
    }

    /**
     * @param $id
     * @return null
     */
    public function read($id)
    {
        $items = [];
        $items = $this->select('data')->where('session_id', '=', $id)
            ->limit('1')
            ->findAll('assoc');

        return isset($items[0]['data']) ? @$items[0]['data'] : null;
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id)
    {
        $status = $this->where('session_id', '=', $id)->trash();

        return $status ? true : false;
    }

    /**
     * @param $max
     * @return bool
     */
    public function gc_session($max)
    {
        $old = time() - $max;
        $this->where('access', '<', $old)->trash();

        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value = null)
    {
        if (!$this->offsetExists($key)) {
            $this->storage[$key] = $value;
        }

        return $this;
    }

    /**
     * @param      $key
     * @param null $default
     * @return null
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->all();
        }
        return $this->offsetExists($key) ? $this->storage[$key] : $default;
    }

    /**
     * Returns all elements
     * If array passed, we will store into storage property
     * as stack
     *
     * @param array $array overwrites values
     * @return array
     */
    public function all($array = [])
    {
        if (!empty($array)) {
            $this->storage = $array;
        }

        return $this->storage;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key = null)
    {
        return $this->offsetExists($key) ? true : false;
    }

    /**
     * @param $key
     * @return void
     * @return void
     */
    public function remove($key = null)
    {
        unset($this->storage[$key]);
    }

    /**
     * @param null $key
     */
    public function delete($key = null)
    {
        /*
         | If null parameter given we will delete all session
         | information and destroy session.
         */
        if (is_null($key)) {
            $this->deleteAll();
        }

        unset($this->storage[$key]);
    }

    /**
     * Delete all session and destroy the session
     */
    public function deleteAll()
    {
        $this->reset();
        session_destroy();
    }

    /**
     * Removes all data and reset the storage to empty array
     *
     * @return $this
     */
    public function reset()
    {
        $this->storage = [];

        return $this;
    }

    /**
     * Check if offset exists
     *
     * @param mixed $key
     * @return boolean true or false
     */
    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get value if exists from storage
     *
     * @param mixed $key
     * @return mixed Can return all value types.
     */
    public function &offsetGet($key)
    {
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = null;
        }
        return $this->storage[$key];
    }

    /**
     * Setting or pushing data into storage
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            array_push($this->storage, $value);
            return;
        }
        $this->storage[$key] = $value;
    }

    /**
     * Key to unset
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->storage[$key]);
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->storage);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->storage);
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        next($this->storage);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->storage);
    }

    /**
     * Checks if current position is valid and return bool value
     *
     * @return bool
     */
    public function valid()
    {
        $key = key($this->storage);
        if ($key === false || $key === null) {
            return false;
        }
        return isset($this->storage[$key]);
    }
}
