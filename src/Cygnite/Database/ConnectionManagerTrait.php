<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database;

use PDO;
use Exception;
use Cygnite\Database\Configure;
use Cygnite\Database\Connections\ConnectionFactory;

trait ConnectionManagerTrait
{
    public static $connections = [];

    public static $instance;

    private static $config;

    public static $connectionObject;

    public static $connectionConfig = [];

    /**
     * @param $key
     * @return null
     */
    public static function get($key)
    {
        return (isset(static::$connections[$key])) ? static::$connections[$key] : null;
    }

    /**
     * @param $config
     * @throws \Exception
     * @return array
     */
    public function parseUrl($config)
    {
        $info = [];

        $info['driver']   = $config['driver'];
        $info['hostname'] = $config['host'];
        $info['port']     = isset($config['port']) ? $config['port'] : '';
        $info['username'] = isset($config['username']) ? $config['username'] : null;
        $info['password'] = isset($config['password']) ? $config['password'] : null;
        $info['database'] = isset($config['database']) ? $config['database'] : null;
        $info['charset']  = (isset($config['charset'])) ? $config['charset'] : '' ;
        $info['collation']  = (isset($config['collation'])) ? $config['collation'] : '' ;
        $info['prefix']  = (isset($config['prefix'])) ? $config['prefix'] : '' ;

        if ($info['hostname'] == 'unix(') {
            $socketDb = null;
            $socketDb =  $info['hostname'] . '/' .  $info['database'];
            if (preg_match_all('/^unix\((.+)\)\/(.+)$/', $socketDb, $matches) > 0) {
                $info['hostname'] = $matches[1][0];
                $info['database'] = $matches[2][0];
            }
        }

        self::setConnectionConfig($info['database'], $info);

        return  $info;
    }

    /**
     * @param $key
     * @param $value
     */
    private static function setConnectionConfig($key, $value)
    {
        self::$connectionConfig[$key] = $value;
    }

    /**
     * @param $key
     * @return null
     */
    public static function getConfig($key)
    {
        return isset(self::$connectionConfig[$key]) ? self::$connectionConfig[$key] : null;
    }

    public function createConnection($config)
    {
        $connection = new ConnectionFactory();

        switch ($config['driver']) {
            case 'mysql':
                return $connection->setConfig($config)->make('MySql');
                break;
            case 'pgsql':
                return $connection->setConfig($config)->make('PgSql');
                break;
            case 'sqlite':
                return $connection->setConfig($config)->make('SqlLite');
                break;
            case 'oracle':
                return $connection->setConfig($config)->make('Oracle');
                break;
            case 'sqlsrv':
                return $connection->setConfig($config)->make('MsSql');
                break;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    /**
     * @param $connectionConfig
     * @return mixed
     * @throws \Exception
     */
    public function setConnection($connectionConfig)
    {
        self::$config = static::parseUrl($connectionConfig);

        try {
            return  static::createConnection(self::$config);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @return null
     */
    public static function getConfiguration()
    {
        if (is_object(static::$config)) {
            return static::$config;
        }

        return null;
    }

    /**
     * @param $connKey
     * @return mixed
     */
    public function getConnection($connKey)
    {
        if (isset(self::$connectionObject[$connKey]) && self::$connectionObject[$connKey] instanceof PDO) {
            return self::$connectionObject[$connKey];
        }

        $config = [];
        $config = Configure::getDatabaseConfiguration();

        foreach ($config as $key => $value) {
            if (trim($value['database']) == trim($connKey)) {
                return self::$connectionObject[$connKey] = $this->setConnection($value);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getDefaultConnection()
    {
        $connection= $this->parseUrl(Configure::getDefault());

        return $connection['database'];
    }
}
