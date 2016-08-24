<?php

namespace Cygnite\Database;

use Closure;
use Cygnite\Common\Singleton;

class Configure extends Singleton
{
    public static $config = [];

    private static $defaultConnection;

    public $default;

    /**
     * @param callable $setup
     */
    public static function database(Closure $setup)
    {
        return $setup(parent::instance());
    }

    /**
     * @param array $config
     *
     * @throws ConfigException
     */
    public function set($config = [])
    {
        if (!is_array($config)) {
            throw new ConfigException('Connection must be an array');
        }

        self::$config = $config;

        $defaultConnection = is_null($this->default) ? 'db' : $this->default;

        if (!is_null($defaultConnection)) {
            $this->setDefaultConnection($config[$defaultConnection]);
        }
    }

    public static function getDatabaseConfiguration()
    {
        return self::$config;
    }

    /**
     * @param $value
     */
    private function setDefaultConnection($value)
    {
        self::$defaultConnection = $value;
    }

    /**
     * @return mixed
     */
    public static function getDefault()
    {
        return isset(self::$defaultConnection) ? self::$defaultConnection : null;
    }
}
