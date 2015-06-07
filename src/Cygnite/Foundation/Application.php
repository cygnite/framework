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
use Cygnite\Translation\Translator;
use Cygnite\DependencyInjection\Container;
use Cygnite\Exception\Handler as ExceptionHandler;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application extends Container
{
    protected static $loader;
    private static $instance;
    private static $version = 'v1.3.2';
    public $aliases = [];
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
     * Set language to the translator
     *
     * @return locale
     */
    public function setLocale()
    {
        $locale = Config::get('global.config', 'locale');
        $fallbackLocale = Config::get('global.config', 'fallback.locale');

        return Translator::make(function ($trans) use ($locale, $fallbackLocale)
        {
            return $trans->setFallback($fallbackLocale)->locale($locale);
        });
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
     * @return callable
     */
    public function getDefinition()
    {
        $this['config.definition'] = function () {
            $class = "\\" . ucfirst(APPPATH) . "\\Configs\\Definitions\\DefinitionManager";
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
    public function registerServiceProvider($services = [])
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
     * Set up all required configurations
     *
     * @param $config
     * @return $this
     */
    public function configure()
    {
        $this->importHelpers();
        $config = [];
        $config =\Cygnite\Helpers\Config::load();

        //Set up configurations for your awesome application
        Config::set('config.items', $config);
        $this->setServices();

        return $this;
    }

    /**
     * Set all configurations and boot application
     *
     * @return $this
     */
    public function bootApplication()
    {
        $this->registerCoreAlias();
        $this->setEnvironment();
        $this['service.provider']();

        return $this;
    }

    public function getCoreAlias()
    {
        return [
            'router'     => 'Cygnite\Base\Router\Router',
            'debugger'   => 'Cygnite\Exception\ExceptionHandler',
            'event'      => 'Cygnite\Base\EventHandler\Event',
        ];
    }


    private function compose($class)
    {
        return $this->makeInstance($class);
    }

    /**
     * We will register all core class into container
     * @return $this
     */
    public function registerCoreAlias()
    {
        foreach ($this->getCoreAlias() as $key => $class) {
            $this->setValue($key, $this->compose("\\".$class));
        }

        return $this;
    }

    private function setEnvironment()
    {
        $app = $this;
        return include __DIR__ . '/../'.'BootStrap'.EXT;
    }

    /**
     * @return Dispatcher
     */
    public function run()
    {
        try {
            return $this->handle();

        } catch (\Exception $e) {

            $this['debugger']->handleException($this);

            if (ENV == 'development') {
                throw $e;
            }

            if ($this['debugger']->isLoggerEnabled() == true) {
                $this['debugger']->log($e);
            }

            $this['debugger']->renderErrorPage($e);
        }
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        /**-------------------------------------------------------
         * Booting completed. Lets handle user request!!
         * Lets Go !!
         * -------------------------------------------------------
         */
        return (new Dispatcher($this))->run();
    }
}
