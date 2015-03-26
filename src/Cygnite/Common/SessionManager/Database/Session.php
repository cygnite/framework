<?php
namespace Cygnite\Common\SessionManager\Database;

use Cygnite\Common\Encrypt;
use Cygnite\Database\ActiveRecord;
use Cygnite\Database\Schema;
use Cygnite\Helpers\Config;
use Cygnite\Common\SessionManager\Session as SessionManager;

class Session extends ActiveRecord
{
    protected $tableName = 'cf_sessions';

    protected $storage;

    private $config = array();

    protected $database;

    private $crypt;

    public function __construct()
    {
        /**
         | Override native session handler
         */
        $this->sessionSaveHandler();

        /**
         | Get user configuration
         */
        $config = array();
        $config =  Config::getConfigItems('config.items');
        $this->config = $config['config.session'];

        /**
         | Set Database and Table for storing
         | session into database
         */
        $this->database($this->config['database_name']);
        $this->table($this->config['table']);

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

    public function start()
    {
        $this->setSessionConfig();

        // Change the session name
        $this->name($this->config['session_name']);
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

        $sessionManager = SessionManager::getInstance();
        // set session hash
        $sessionManager->setHash();
        // use cookie
        $sessionManager->useOnlyCookie();

        $sessionManager->setCookieParams($secure, $httpOnly);
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

    /**
     * @param null $table
     */
    public function table($table = null)
    {
        $this->tableName = $table;
    }

    /**
     * @param null $database
     */
    public function database($database = null)
    {
        $this->database = $database;
    }

    /**
     * @return null
     */
    public function getDatabaseName()
    {
        return isset($this->database) ? $this->database : null;
    }

    /**
     * @return null|string
     */
    public function getTable()
    {
        return isset($this->tableName) ? $this->tableName : null;
    }

    /**
     * Set session save handler to override native handler
     */
    public function sessionSaveHandler()
    {
        @session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc_session')
        );
    }

    /**
     * Open an connection
     * @return bool
     */
    public function open()
    {
        $this->createTableIfNotExists();

        return true;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function write($id, $data)
    {
        $access = time();
        $this->session_id = (string) $id;
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
        $items = array();
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
        /*$connection = $this->fluentQuery()->getDatabaseConnection();
        $stmt = $connection->prepare("DELETE FROM sessions WHERE session_id = :session_id");
        $stmt->bindValue(':session_id', $id , \PDO::PARAM_STR);*/

        $status = $this->where('session_id', '=', $id)->trash();

        return $status ? true: false;
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
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * @param $key
     * @return null
     */
    public function get($key)
    {
        return isset($this->storage[$key]) ? $this->storage[$key] : null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
       return isset($this->storage[$key]) ? true : false;
    }

    /**
     * @param $key
     */
    public function delete($key)
    {
        unset($this->storage[$key]);
        session_destroy();
    }

    /**
     * We will create session schema if not exists
     */
    public function createTableIfNotExists()
    {
        $me = $this;

        Schema::instance(
            $this,
            function($table) use ($me) {
                $table->tableName = $me->getTable();

                /**
                | Check if table already exists
                | if not we will create an table to store session info
                 */
                if (!$table->hasTable()->run()) {

                    $table->create(
                        array(
                            array('name'=> 'id', 'type' => 'int', 'length' => 11,
                                'increment' => true, 'key' => 'primary'),
                            array('name'=> 'access', 'type' => 'int', 'length' =>11),
                            array('name'=> 'data', 'type' => 'text'),
                            array('name'=> 'session_id', 'type' => 'string', 'length' => 128),
                        ),
                        'InnoDB',
                        'latin1'
                    )->run();
                }
            }
        );
    }

}
