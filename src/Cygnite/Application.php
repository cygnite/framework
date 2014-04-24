<?php
namespace Cygnite;

use Closure;
use Exception;
use ReflectionClass;
use Cygnite\Strapper;
use Cygnite\Reflection;
use Cygnite\Helpers\Url;
use ReflectionProperty;
use Cygnite\Base\Router;
use Cygnite\Base\Dispatcher;
use Cygnite\Helpers\Config;
use Apps\Configs\Definitions\DefinitionManager;
use Cygnite\Base\DependencyInjection\Container;


if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so that I can send you a copy immediately.
 *
 * @Package             :  Cygnite Framework BootStrap file
 * @Filename            :  Application.php
 * @Description         :  Cygnite Application will take care of all your
 *                         auto loading files and save and fetch you the
 *                         data from container
 *
 * @Author              :  Sanjoy Dey
 * @Copyright           :  Copyright (c) 2013 - 2014,
 * @Link	            :  http://www.cygniteframework.com
 * @Since	            :  Version 1.0
 * @File Source
 *
 */

class Application extends Container
{

    private static $instance;

    protected static $loader;

    public $aliases = array();

    public $namespace = '\\Apps\\Controllers\\';

    /**
     * ---------------------------------------------------
     * Cygnite Constructor
     * ---------------------------------------------------
     * You cannot directly create object of Application
     * instance method will dynamically return you instance of
     * Application
     *
     * @param Inflector
     *
     */

    protected function __construct(Inflector $inflection)
    {
        self::$loader = new AutoLoader($inflection);
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments = array())
	{
		if ($method == 'instance') {
			return call_user_func_array(
                array(new Application(Inflector::instance()), 'getInstance'),
                $arguments
            );
		}
	}


    /**
     * ----------------------------------------------------
     * Return instance of Application or Closure instance of Cygnite
     * ----------------------------------------------------
     *
     * @param callable Closure $callback
     * @return object
     */
	 public function getInstance(Closure $callback = null)
	 {
        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback(new Application(Inflector::instance()));
        } else {
           return new Application(Inflector::instance());
            }
        }

    /**
     * set all your configurations
     * @param $configurations
     * @return $this
     */
    public function setConfiguration($configurations)
    {
        $this->setValue('config', $configurations['app'])
             ->setValue('event', $configurations['event'])
             ->setValue('boot', new Strapper)
             ->setValue('router', new Router);

        return $this;
    }

	public function getAliases($key)
	{
		return isset($this->aliases) ? $this->aliases : null;
	}


	/**
     * Get framework version
	 * @access public
	 */
    public static function version()
    {
        return CF_VERSION;
    }

    /**
     * @warning You can't change this!
     * @return string
     */
    public static function poweredBy()
    {
        return 'Cygnite Framework - '.CF_VERSION.' Powered by -
            Sanjoy Productions (<a href="http://www.cygniteframework.com">
            http://www.cygniteframework.com
            </a>)';
    }

    public function getDefaultConnection()
    {

    }

    /**
     * Start booting and handler all user request
     * @return Dispatcher
    */
    public function boot()
    {
        Url::instance($this['router']);
       //Set up configurations for your awesome application
        Config::set('config_items', $this['config']);
       //Set URL base path.
       Url::setBase(
       	(Config::get('global_config', 'base_path') == '') ?
            $this['router']->getBaseUrl()  :
       	    Config::get('global_config', 'base_path')
       	);

       //initialize framework
        $this['boot']->initialize();
        $this['boot']->terminate();

      /**-------------------------------------------------------
       * Booting completed. Lets handle user request!!
       * Lets Go !!
       * -------------------------------------------------------
       */
        return new Dispatcher($this['router']);
    }

    /**
     * @param $directories
     * @return mixed
     */
    public function registerDirectories($directories)
    {
        return self::$loader->registerDirectories($directories);
}

    /**
     * Import files using import function
     * @param $path
     * @return bool
     */
    public static function import($path)
    {
        return self::$loader->import($path);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setValue($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * @param $class
     * @return string
     */
    public function getController($class)
    {
        return
            $this->namespace.Inflector::instance()->covertAsClassName(
                $class
            ).'Controller';
    }

    /**
     * @param $actionName
     * @return string
     */
    public function getActionName($actionName)
    {
        return Inflector::instance()->toCameCase(
            (!isset($actionName)) ? 'index' : $actionName
        ).'Action';
    }

    /**
     * @return callable
     */
    public function getDefinition()
    {
        $this['config.definition'] = function() {
            return new DefinitionManager;
        };

        return $this['config.definition'];
    }

    /**
     * Inject all your properties into controller at run time
     * @param $instance
     * @param $controller
     * @throws Exception
     */
    public function propertyInjection($instance, $controller)
    {
        $definition = $this->getDefinition();

        $injectableDefinitions = $definition()->getPropertyDependencies();

        $this->setPropertyInjection($injectableDefinitions);

        $dependencies = $this->getDefinitions($controller);

        if (array_key_exists($controller, $this->definitions)) {

            $property = key($dependencies);

            $reflection = new Reflection();
            $reflection->setClass($controller);

            if (!$reflection->reflectionClass->hasProperty($property)) {
                throw new Exception(
                    sprintf("Property %s is not defined in $controller controller", $property)
                );
            }

            $reflection->makePropertyAccessible($property);
            $reflection->reflectionProperty->setValue(
                $instance, $dependencies[$property]
            );
        }
    }
}
