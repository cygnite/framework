<?php
namespace Cygnite\Helpers;

use Exception;
use Cygnite\Proxy\StaticResolver;
use InvalidArgumentException;

if (defined('CF_SYSTEM') == false) {
    exit('No External script access allowed');
}
/**
 * Class Config
 * This class used to load all configurations files in order to
 * quick access on user request
 *
 * @package Cygnite\Helpers
 */
class Config extends StaticResolver
{
    private static $config = [];

    protected $configuration = [];

    public static $paths = [];

    public $files = [
        'global.config' => 'application',
        'config.database' => 'database',
        'config.session' => 'session',
        'config.view' => 'view',
    ];

    /**
     * Get the configuration.
     *
     * @param string $arrKey get config based on key
     *
     * @param bool $keyValue get config value
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function get($arrKey, $keyValue = false)
    {
        $config = [];

        $config = $this->getConfigItems('config.items');

        if ($arrKey === null) {
            throw new InvalidArgumentException(
                'Cannot pass null argument to '.__METHOD__
            );
        }

        if (is_array($config)) {
            if (false !== array_key_exists($arrKey, $config) && $keyValue === false) {
                return $config[$arrKey];
            }

            if (false !== array_key_exists($arrKey, $config) && $keyValue !== false) {
                return $config[$arrKey][$keyValue];
            }
        }

    }//end of getConfig()

    /**
     * Store new configurations
     * @param       $name
     * @param array $values
     */
    protected function set($name, $values = [])
    {
        self::$config[$name] = $values;

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

    /**
     * @return array
     */
    protected function getPaths()
    {
        return isset(static::$paths) ? static::$paths : [];
    }

    /**
     * @param $key
     * @return null
     * @throws \InvalidArgumentException
     */
    protected function getConfigItems($key)
    {
        if (is_null($key) == true) {
            throw new InvalidArgumentException(
                'Cannot pass null argument to '.__METHOD__
            );
        }

        return isset(self::$config[strtolower($key)]) ?
            self::$config[strtolower($key)] :
            null
            ;

    }
    /*
     * Import application configurations
     */
    protected function load()
    {
        $this->importConfigurations();

        return $this->configuration;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function importConfigurations()
    {
        $configPath = "";
        $configPath = static::$paths['app.path'].static::$paths['app.config']['directory'];
        $files = [];
        $files = array_merge($this->files, static::$paths['app.config']['files']);

        if (isset(
            $this->configuration['global.config'],
            $this->configuration['config.database'],
            $this->configuration['config.session']
        )) {
            return $this->configuration;
        }

        foreach ($files as $key => $file) {

            if (file_exists($configPath.$file.EXT)) {
                /**
                 | We will include configuration file into array only first time
                 |
                 */
                if (!isset($this->configuration[$key])){
                    $this->configuration[$key] = include $configPath.$file.EXT;
                }

            } else {
                throw new Exception("File not exists on the path ".$configPath.$file.EXT);
            }
        }
    }
}
