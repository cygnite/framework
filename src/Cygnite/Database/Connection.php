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

abstract class Connection
{
    public static $connections = [];

    public static $instance;

    private static $config;

    public static $connectionObject;

    public static $connectionConfig = [];

    /**
     * @var array
     */
    public static $options = array(
        PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE              =>  PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS      =>  PDO::NULL_NATURAL,
        PDO::ATTR_PERSISTENT        =>  true,
        PDO::ATTR_STRINGIFY_FETCHES	=>  false,
        PDO::ATTR_EMULATE_PREPARES  =>  false,
    );

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
    public static function parseUrl($config)
    {
        $info = [];

        $info['driver']   = $config['driver'];
        $info['hostname'] = $config['host'];
        $info['port']     = isset($config['port']) ? $config['port'] : '';
        $info['username'] = isset($config['username']) ? $config['username'] : null;
        $info['password'] = isset($config['password']) ? $config['password'] : null;
        $info['database'] = isset($config['database']) ? $config['database'] : null;
        $info['charset']  = (isset($config['charset'])) ? $config['charset'] : '' ;
        $config = [];

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

    /**
     * @param $connectionConfig
     * @return mixed
     * @throws \Exception
     */
    public static function setConnection($connectionConfig)
    {
        $info = static::parseUrl($connectionConfig);

        self::$config = $info;

        $dns = $info['driver']
            .':host='.$info['hostname'].$info['port'].
            ';dbname='.$info['database'];

        try {
            if (!array_key_exists($info['database'], static::$connections)) {

                static::$connections[$info['database']] = new PDO(
                    $dns,
                    $info['username'],
                    $info['password'],
                    self::$options
                );

                //static::$connections[$info['database']]->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                static::$connections[$info['database']]->setAttribute(
                    PDO::MYSQL_ATTR_INIT_COMMAND,
                    'SET NAMES '.$info['charset']
                );
                static::$connections[$info['database']]->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
                static::$connections[$info['database']]->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_OBJ
                );

            }

            return static::$connections[$info['database']];

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
    public static function getConnection($connKey)
    {
        if (isset(self::$connectionObject[$connKey]) && self::$connectionObject[$connKey] instanceof PDO) {
            return self::$connectionObject[$connKey];
        }

        $config = [];

        $config = Configure::getDatabaseConfiguration();

        foreach ($config as $key => $value) {

            if (trim($value['database']) == trim($connKey)) {
                self::$connectionObject[$connKey] = self::setConnection($value);
                break;
            }
        }

        return self::$connectionObject[$connKey];
    }

    /**
     * @return mixed
     */
    public static function getDefaultConnection()
    {
        $connection= self::parseUrl(Configure::getDefault());

        return $connection['database'];
    }
}
