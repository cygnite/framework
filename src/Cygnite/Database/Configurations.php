<?php
namespace Cygnite\Database;

use Cygnite\Common\Singleton;
use Closure;

class Configurations extends Singleton
{
    public $config = array();

    public $connections = array();

    private $defaultConnection;

    public static function initialize(Closure $setup)
    {
        $setup(parent::instance());
    }

    public function setConfig($config = array(), $defaultConnection = 'db')
    {
        if (!is_array($config)) {
            throw new ConfigException("Connections must be an array");
        }

        $this->connections = $config;

        if ($defaultConnection) {
            $this->setDefaultConnection($defaultConnection);
        }
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }

    public function setDefaultConnection($name)
    {
        $this->defaultConnection = $this->connections[$name];
    }
}