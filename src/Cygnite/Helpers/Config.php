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

    private static $_config = array();

    private $configuration = array();


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
        $config = array();

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
    protected function set($name, $values = array())
    {
        self::$_config[$name] = $values;

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

        return isset(self::$_config[strtolower($key)]) ?
            self::$_config[strtolower($key)] :
            null
            ;

    }
    /*
     * Import application configurations
     */
    protected function load()
    {
        $config = array();

        $this->importConfigurations('global.config', 'application');
        $this->importConfigurations('config.database', 'database');
        $this->importConfigurations('config.session', 'session');
        $this->importConfigurations('config.autoload', 'autoload');
        $this->importConfigurations('config.router', 'routerconfig', '');

        return $this->configuration;
    }

    /**
     * @param        $name
     * @param        $fileName
     * @param string $configDir
     * @return mixed
     * @throws \Exception
     */
    private function importConfigurations($name, $fileName, $configDir = 'configs')
    {
        $configPath = "";
        $configPath = strtolower(APPPATH).DS.$configDir.DS;

        if (file_exists($configPath.$fileName.EXT)) {
           $this->configuration[$name] = include_once $configPath.$fileName.EXT;
        } else {
            throw new Exception("File not exists on the path ".$configPath.$fileName.EXT);
        }
    }
}
