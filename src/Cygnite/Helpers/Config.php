<?php
namespace Cygnite\Helpers;

use Cygnite\Proxy\StaticResolver;
use Cygnite\Common\ArrayManipulator\ArrayAccessor;

if (defined('CF_SYSTEM') == false) {
    exit('No External script access allowed');
}
/**
 * Class Config
 * This class used to load all configurations files in order to
 * quick access of user request
 *
 * @package Cygnite\Helpers
 */
class Config extends StaticResolver
{
    public static $config = [];

    public static $paths = [];

    public $files = [
        'global.config'   => 'application',
        'config.database' => 'database',
        'config.session'  => 'session',
        'config.view'     => 'view',
    ];

    public $default = 'config.items';

    /**
     *  Get the configuration by index
     *
     * @param      $key
     * @param bool $value
     * @return mixed|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function get($key, $value = false)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException('Null argument passed to '.__METHOD__);
        }

        $config = [];
        $config = self::$config;

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
        return ArrayAccessor::make(function($accessor) use ($key, $config)
            {
                return $accessor->set($config)->toString($key);
            });
    }

    /**
     * Set configuration parameter
     *
     * @param       $key
     * @param array $value
     */
    protected function set($key, $value = [])
    {
        self::$config[$key] = $value;
    }

    /**
     * @param $paths
     * @return $this
     */
    protected function setPaths($paths)
    {
        static::$paths = $paths;

        return $this;
    }

    protected function addConfigFile($array)
    {
        $this->files = array_merge($this->files, $array);

        return $this;
    }

    /**
     * @return array
     */
    protected function getPaths()
    {
        return isset(static::$paths) ? static::$paths : [];
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
     * @return array
     * @throws \Exception
     */
    private function importConfigurations()
    {
        $configPath = "";
        $configPath = static::$paths['app.path'].DS.toPath(static::$paths['app.config']['directory']);
        $files = [];
        $files = array_merge($this->files, static::$paths['app.config']['files']);

        foreach ($files as $key => $file) {

            if (!file_exists($configPath.$file.EXT)) {
                throw new \Exception("File doesn't exists in the path ".$configPath.$file.EXT);
            }

            /**
            | We will include configuration file into array only
            | for the first time
             */
            if (!isset(self::$config[$key])){
                Config::set($key, include $configPath.$file.EXT);
            }
        }
    }
}
