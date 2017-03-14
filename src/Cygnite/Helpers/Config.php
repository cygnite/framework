<?php

namespace Cygnite\Helpers;

use Cygnite\Common\ArrayManipulator\ArrayAccessor;
use Cygnite\Proxy\StaticResolver;

if (defined('CF_SYSTEM') == false) {
    exit('No External script access allowed');
}
/**
 * Class Config
 *
 * This class used to load all configurations files in order to
 * quick access of user request.
 */
class Config extends StaticResolver
{
    public static $config = [];

    public static $path = [];

    public $files = [
        'global.config'   => 'application',
        'config.database' => 'database',
        'config.session'  => 'session',
        'config.view'     => 'view',
    ];

    public $default = 'config.items';

    /**
     * Set the Configuration path and customizable files array
     *
     * @param $path
     * @param array $files
     */
    protected function init(string $path, array $files = [])
    {
        static::$path = $path;
        $this->addConfigFile($files);
        $this->load();
    }

    /**
     *  Get the configuration by index.
     *
     * @param      $key
     * @param bool $value
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed|null
     */
    protected function get($key, $value = false)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException('Null argument passed to '.__METHOD__);
        }

        $config = [];
        $config = static::$config;

        if (empty($config)) {
            throw new \Exception('Config stack is empty!');
        }

        if ($value == false && array_key_exists($key, $config)) {
            return isset($config[$key]) ? $config[$key] : null;
        }

        if (array_key_exists($key, $config) && $value == true) {
            return $config[$key][$value];
        }

        /*
         | We will access array value as string with dot separator
         | 'module.config' => [
         |    "config"  => [
         |        "name" => "Welcome To Module"
         |    ]
         | ]
         | Config::get('module-config.config.name');
         */
        return ArrayAccessor::make($config, function ($a) use ($key) {
            return $a->toString($key);
        });
    }

    /**
     * Set configuration parameter.
     *
     * @param string $key
     * @param array $value
     */
    protected function set($key, $value = [])
    {
        static::$config[$key] = $value;
    }

    /**
     * Return all Config variables
     *
     * @return array
     */
    protected function all()
    {
        return static::$config;
    }

    /**
     * Set the configuration files path
     *
     * @param $path
     * @return $this
     */
    protected function setPath(string $path) : Config
    {
        static::$path = $path;

        return $this;
    }

    /**
     * Add additional files into array, Config will
     * load additional config files at boot time
     *
     * @param $array
     * @return $this
     */
    protected function addConfigFile(array $array) : Config
    {
        $this->files = array_merge($this->files, $array);

        return $this;
    }

    /**
     * Return the application configuration path
     *
     * @return string
     */
    protected function getPath() : string
    {
        return isset(static::$path) ? static::$path : [];
    }

    /*
     * Import application configurations
     */
    protected function load()
    {
        //Set up configurations for your awesome application
        $this->importConfigurations();

        return true;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    protected function importConfigurations()
    {
        foreach ($this->files as $key => $file) {
            if (!file_exists(static::$path.DS.$file.'.php')) {
                throw new \Exception("File doesn't exists in the path ".static::$path.DS.$file.'.php');
            }

            /*
            | We will include configuration file into array only
            | for the first time
             */
            if (!isset(static::$config[$key])) {
                static::set($key, include static::$path.DS.$file.'.php');
            }
        }
    }
}
