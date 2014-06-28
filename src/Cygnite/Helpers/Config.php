<?php
namespace Cygnite\Helpers;

use Exception;
use Cygnite\Proxy\StaticResolver;
use InvalidArgumentException;

if (defined('CF_SYSTEM') == false) {
    exit('No External script access allowed');
}
/**
 * Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package              :  Packages
 * @Sub Package          :  Helpers
 * @Filename             :  Config
 * @Description          :  This file is used to load all framework configurations
 *                          via Registry and store it in order to use it later.
 * @Author               :  Cygnite dev team
 * @Copyright            :  Copyright (c) 2013 - 2014,
 * @Link	             :  http://www.cygniteframework.com
 * @Since	             :  Version 1.0
 * @Filesource
 * @Warning              :  Any changes in this library can cause
 *                          abnormal behaviour of the framework.
 *
 *
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
