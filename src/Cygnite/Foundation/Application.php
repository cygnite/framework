<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Foundation;

use Closure;
use Exception;
use ReflectionClass;
use Cygnite\Strapper;
use Cygnite\Reflection;
use ReflectionProperty;
use Cygnite\Base\Router;
use Cygnite\Helpers\Inflector;
use Cygnite\Base\Dispatcher;
use Cygnite\Helpers\Config;
use Cygnite\Common\UrlManager\Url;
use Apps\Configs\Definitions\DefinitionManager;
use Cygnite\DependencyInjection\Container;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application extends Container
{

    private static $instance;

    protected static $loader;

    public $aliases = array();

    public $namespace = '\\Apps\\Controllers\\';
	
	private static $version = 'v1.2';

    /**
     * ---------------------------------------------------
     * Cygnite Constructor
     * ---------------------------------------------------
     * You cannot directly create object of Application
     * instance method will dynamically return you instance of
     * Application
     *
     * @param Inflector  $inflection
     * @param Autoloader $loader
     * @return \Cygnite\Foundation\Application
     */

    protected function __construct(Inflector $inflection=null,Autoloader $loader=null)
    {
        $inflection = $inflection ?: new Inflector();
        self::$loader = $loader ? :new AutoLoader($inflection);
    }

    /**
     * ----------------------------------------------------
     *  Instance
     * ----------------------------------------------------
     *
     * Returns a Instance for a Closure Callback and general calls.
     * @param Closure $callback
     * @return Application
     */
    public static function instance(Closure $callback = null)
    {
        if (!is_null($callback) && $callback instanceof Closure) {

            if(static::$instance instanceof Application)
                return $callback(static::$instance);

        } elseif (static::$instance instanceof Application) {
            return static::$instance;
        }

        return static::getInstance();
    }

    /**
     * ----------------------------------------------------
     * Return instance of Application
     * ----------------------------------------------------
     *
     * @param Inflector  $inflection
     * @param Autoloader $loader object
     * @internal param \Cygnite\Inflector $inflector object
     * @return Application object
     */
    public static function getInstance(Inflector $inflection = null, Autoloader $loader = null)
	{
        if(static::$instance instanceof Application)
            return static::$instance;

        $inflection = $inflection ?:new Inflector();
        $loader = $loader ?:new AutoLoader($inflection);

        return static::$instance = new Application($inflection,$loader);
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
        return static::$version;
    }

    /**
     * @warning You can't change this!
     * @return string
     */
    public static function poweredBy()
    {
        return 'Cygnite Framework - '.static::$version.' Powered by -
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
        Config::set('config.items', $this['config']);
        //Set URL base path.
        Url::setBase(
        (Config::get('global.config', 'base_path') == '') ?
            $this['router']->getBaseUrl()  :
            Config::get('global.config', 'base_path')
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
     * @param      $class
     * @param  $dir
     * @return string
     */
    public function getController($class, $dir = '')
    {
        $dir = ($dir !== '') ? $dir.'\\' : '';

        return
            $this->namespace.$dir.Inflector::instance()->classify(
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
}
