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
use Cygnite\Base\Router\Router;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Inflector;
use Cygnite\Base\Request\Dispatcher;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Translation\Translator;
use Cygnite\Container\Container;
use Cygnite\Exception\Handler as ExceptionHandler;
use Tracy\Helpers;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application extends Container
{
    protected static $loader;
    private static $instance;
    private static $version = 'v2.0';
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
            throw new \Exception("Application::service() accept only valid closure callback");
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
            $paths = Config::getPaths();
            return include $paths['app.path']. DS.$paths['app.config']['directory'] .'services' . EXT;
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
     * @internal param $config
     * @return $this
     */
    public function configure()
    {
        //$this->importHelpers();
        $this->setPaths(realpath(CYGNITE_BASE.DS.CF_BOOTSTRAP.DS.'config.paths'.EXT));

        //Set up configurations for your awesome application
        \Cygnite\Helpers\Config::load();

        $this->setServices();

        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPaths($path)
    {
        Config::setPaths(require $path);

        return $this;
    }

    /**
     * Set all configurations and boot application
     *
     * @return $this
     */
    public function bootApplication()
    {
        /*
        | -------------------------------------------------------------------
        | Check if script is running via cli and return false
        | -------------------------------------------------------------------
        |
        | We will check if script running via console
        | then we will return from here, else application
        | fall back down
        */
        if (isCli()) {
            return $this;
        }
    
        $this->registerCoreAlias();
        $this->setEnvironment();
        $this->beforeBootingApplication();
        
        $this['debugger']->handleException();
        $this['service.provider']();

        $this->afterBootingApplication();

        return $this;
    }

    /**
     * We will activate middle ware events if set as true in
     * Configs/application.php
     * @return mixed
     */
    public function activateEventMiddleWare()
    {
        $eventMiddleware = Config::get('global.config', 'activate.event.middlewares');

        if ($eventMiddleware) {
            $class = "\\".APP_NS."\\Middlewares\\Events\\Event";
            return (new $class)->register();
        }
    }


    /**
     * Attach all application events to event handler.
     *
     */
    public function attachEvents()
    {
        if (!empty($this['event']->getAppEvents())) {
            $events = [];
            $events = $this['event']->getAppEvents();

            foreach ($events as $event => $namespace) {
                // attach all before and after event to handler
                $this['event']->attach("$event", $namespace);
            }
        }
    }

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware
     *
     * @return bool
     */
    public function beforeBootingApplication()
    {
        if ($this['event']->getAppEvents() == false) {
            return true;
        }

        $this->attachEvents();

        return $this['event']->trigger(__FUNCTION__, $this);
    }

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware
     *
     * @return bool
     */
    public function afterBootingApplication()
    {
        if ($this['event']->getAppEvents() == false) {
            return true;
        }

        return $this['event']->trigger(__FUNCTION__, $this);
    }

    /**
     * @return array
     */
    public function getCoreAlias()
    {
        return [
            'router'     => 'Cygnite\Base\Router\Router',
            'debugger'   => 'Cygnite\Exception\ExceptionHandler',
            'event'      => 'Cygnite\Base\EventHandler\Event',
        ];
    }

    /**
     * Create an instance of the class and return it
     *
     * @param $class
     * @return mixed
     */
    public function compose($class)
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

    /**
     * @return mixed
     */
    private function setEnvironment()
    {
        $app = $this;
        return include __DIR__ . '/../'.'BootStrap'.EXT;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function run()
    {
        /**
         | We will check if script running via console
         | then we will return out from here, else application
         | fall back down
         */
        if (isCli()) {
            return $this;
        }

        try {
            return $this->handle();
        } catch (\Exception $e) {
            if (ENV == 'development') {
                throw $e;
            }

            if (ENV == 'production') {

                /**
                 * We will log exception if logger enabled
                 */
                if ($this['debugger']->isLoggerEnabled()) {
                    $this['debugger']->log($e);
                }

                $this['debugger']->renderErrorPage($e);
            }
        }
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        $this->activateEventMiddleWare();
        /**-------------------------------------------------------
         * Booting completed. Lets handle user request!!
         * Lets Go !!
         * -------------------------------------------------------
         */
        return (new Dispatcher($this))->run();
    }
}
