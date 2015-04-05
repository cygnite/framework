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
use Cygnite\Strapper;
use Cygnite\Base\Router\Router;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Inflector;
use Cygnite\Base\Request\Dispatcher;
use Cygnite\Common\UrlManager\Url;
use Cygnite\DependencyInjection\Container;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application extends Container
{
    protected static $loader;
    private static $instance;
    private static $version = 'v1.3.2';
    public $aliases = array();
    public $namespace = '\\Controllers\\';

    /**
     * ---------------------------------------------------
     * Cygnite Constructor
     * ---------------------------------------------------
     * You cannot directly create object of Application
     * instance method will dynamically return you instance of
     * Application
     *
     * @param Autoloader $loader
     * @return \Cygnite\Foundation\Application
     */

    protected function __construct(Autoloader $loader = null)
    {
        self::$loader = $loader ? : new AutoLoader();
    }

    /**
     * ----------------------------------------------------
     *  Instance
     * ----------------------------------------------------
     *
     * Returns a Instance for a Closure Callback and general calls.
     *
     * @param Closure $callback
     * @return Application
     */
    public static function instance(Closure $callback = null)
    {
        if (!is_null($callback) && $callback instanceof Closure) {

            if (static::$instance instanceof Application) {
                return $callback(static::$instance);
            }

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
     * @param Autoloader $loader
     * @return Application object
     */
    public static function getInstance(Autoloader $loader = null)
    {
        if (static::$instance instanceof Application) {
            return static::$instance;
        }

        $loader = $loader ? : new AutoLoader();

        return static::$instance = new Application($loader);
    }

    /**
     * Get framework version
     *
     * @return string
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
        return 'Cygnite Framework - ' . static::$version . ' (<a href="http://www.cygniteframework.com">
            http://www.cygniteframework.com
            </a>)';
    }

    /**
     * Import files using import function
     *
     * @param $path
     * @return bool
     */
    public static function import($path)
    {
        return self::$loader->import($path);
    }

    /**
     * Service Closure callback
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public static function service(Closure $callback)
    {
        if (!$callback instanceof Closure) {
            throw new Exception("Application::service() accept only valid closure callback");
        }

        return $callback(static::$instance);
    }

    /**
     * Set up all required configurations
     *
     * @param $config
     * @return $this
     */
    public function setConfiguration($config)
    {
        $this->importHelpers();

        $this->setValue('config', $config)
             ->setValue('boot', new Strapper)
             ->setValue('router', new Router)
             ->setServices();

        return $this;
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

    public function getAliases($key)
    {
        return isset($this->aliases) ? $this->aliases : null;
    }

    /**
     * We will include services
     * @return $this
     */
    public function setServices()
    {
        $this['service.provider'] = function () {
            return include APPPATH . DS . 'configs' . DS . 'services' . EXT;
        };

        return $this;
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
     * @param      $class
     * @param      $dir
     * @return string
     */
    public function getController($class, $dir = '')
    {
        $dir = ($dir !== '') ? $dir . '\\' : '';

        return
            "\\" . ucfirst(APPPATH) . $this->namespace . $dir . Inflector::classify(
                $class
            ) . 'Controller';
    }

    /**
     * @param $actionName
     * @return string
     */
    public function getActionName($actionName)
    {
        return Inflector::camelize(
            (!isset($actionName)) ? 'index' : $actionName
        ) . 'Action';
    }

    /**
     * @return callable
     */
    public function getDefinition()
    {
        $this['config.definition'] = function () {
            $class = "\\" . ucfirst(APPPATH) . "\Configs\Definitions\DefinitionManager";
            return new $class;
        };

        return $this['config.definition'];
    }

    /**
     * We will register all service providers into application
     *
     * @param array $services
     * @return $this
     */
    public function registerServiceProvider($services = array())
    {
        foreach ($services as $key => $serviceProvider) {
            $this->createProvider('\\' . $serviceProvider)->register($this);
        }

        return $this;
    }

    /**
     * Create a new provider instance.
     *
     * @param             $provider
     * @return mixed
     */
    public function createProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * @param $key
     * @param $class
     * @return void
     */
    public function setServiceController($key, $class)
    {
        $this[$key] = function () use ($class) {
            $serviceController = $this->singleton('\Cygnite\Mvc\Controller\ServiceController');
            $instance = new $class($serviceController, $this);
            $serviceController->setController($class);

            return $instance;
        };
    }

    /**
     * We will include Supporting Helpers
     * @return mixed
     * @issue Path Issue Fixed And Code Suggested By Peter Moulding https://www.linkedin.com/profile/view?id=1294355
     */
    public function importHelpers()
    {
        return include __DIR__ . '/../'.'Helpers/Support'.EXT;
    }

    /**
     * Start booting and handler all user request
     *
     * @return Dispatcher
     */
    public function boot()
    {
        //Set up configurations for your awesome application
        Config::set('config.items', $this['config']);
        //Set URL base path.
        Url::setBase(
            (Config::get('global.config', 'base_path') == '') ?
                $this['router']->getBaseUrl() :
                Config::get('global.config', 'base_path')
        );

        $this['service.provider']();
        //initialize framework
        $this['boot']->initialize($this);
        $this['boot']->terminate();

        return $this;
    }

    /**
     * @return Dispatcher
     */
    public function run()
    {
        /**-------------------------------------------------------
         * Booting completed. Lets handle user request!!
         * Lets Go !!
         * -------------------------------------------------------
         */
        $dispatcher = new Dispatcher($this);
        return $dispatcher->run();
    }
}
