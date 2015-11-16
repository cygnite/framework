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
use Tracy\Helpers;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Inflector;
use Cygnite\Base\Router\Router;
use Cygnite\Container\Container;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Translation\Translator;
use Cygnite\Base\Request\Dispatcher;
use Cygnite\Foundation\Http\ResponseInterface;
use Cygnite\Exception\Handler as ExceptionHandler;


if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application extends Container implements ApplicationInterface
{
    /**
     * Store instance of the Application
     *
     * @var instance
     */
    public static $instance;
    /**
     * The Cygnite Framework Version.
     * @var string
     */
    const VERSION = 'v2.0';
    /**
     * @var array
     */
    public $aliases = [];

    /**
     * Indicates if the application is "booted" or not.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * @var string
     */
    public $namespace = '\\Controllers\\';

    /**
     * ---------------------------------------------------
     * Cygnite Constructor
     * ---------------------------------------------------
     * You cannot directly create object of Application
     * instance method will dynamically return you instance of
     * Application
     *
     * @param $argument
     * @return \Cygnite\Foundation\Application
     */

    protected function __construct($argument = [])
    {
    }

    /**
     * Returns a Instance of Application either as Closure
     * or static instance.
     *
     * @param Closure $callback
     * @return Application
     */
    public static function instance(Closure $callback = null, $argument = [])
    {
        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback(static::getInstance($argument));
        }

        return static::getInstance($argument);
    }

    /**
     * ----------------------------------------------------
     * Return instance of Application
     * ----------------------------------------------------
     *
     * @param Autoloader $loader
     * @return Application object
     */
    public static function getInstance($argument = [])
    {
        if (static::$instance instanceof Application) {
            return static::$instance;
        }

        return static::$instance = new Application($argument);
    }

    /**
     * Get framework version
     *
     * @return string
     */
    public static function version()
    {
        return static::VERSION;
    }

    /**
     * @warning You can't change this!
     * @return string
     */
    public static function poweredBy()
    {
        return 'Cygnite PHP Framework - ' . static::version() . ' (<a href="http://www.cygniteframework.com">
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
        return (new AutoLoader())->import($path);
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
     * Override parent method
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        return parent::set($key, $value);
    }

    public function getAliases($key)
    {
        return isset($this->aliases) ? $this->aliases : null;
    }

    /**
     * We will register all class definition for
     * dependency injections
     */
    public function registerClassDefinition()
    {
        $path = './'.APPPATH.DS;
        $definitions = include realpath($path.'Configs'.DS.'definitions'.DS.'configuration'.EXT);
        $this->set('definition.config', $definitions);

        return $this;
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
        return (new AutoLoader())->registerDirectories($directories);
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
     * @issue Path Issue Fixed And Identified By Peter Moulding https://www.linkedin.com/profile/view?id=1294355
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

        $this->bootInternals();
        $this->booted = true;

        return $this;
    }

    /**
     * @return $this
     */
    private function bootInternals()
    {
        if ($this->booted) return;

        $this->registerCoreAlias();
        $this->setEnvironment();
        $this->beforeBootingApplication();
        $this['debugger']->handleException();
        $this['service.provider']();
        $this->afterBootingApplication();

        return $this;
    }

    /**
     * Indicate if Application booted or not
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * We will activate middle ware events if set as true in
     * Configs/application.php
     * @return mixed
     */
    public function activateEventMiddleWare()
    {
        $eventMiddleware = Config::get('global.config', 'activate.event.middleware');

        if ($eventMiddleware) {
            $class = "\\".APP_NS."\\Middleware\\Events\\Event";
            return (new $class)->register();
        }
    }


    /**
     * Attach all application events to event handler.
     *
     */
    public function attachEvents()
    {
        $appEvents = $this['event']->getAppEvents();

        if (!empty($appEvents)) {

            foreach ($appEvents as $event => $namespace) {
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
    public function compose($class, $arguments = [])
    {
        return $this->makeInstance($class, $arguments);
    }

    /**
     * We will register all core class into container
     * @return $this
     */
    public function registerCoreAlias()
    {
        foreach ($this->getCoreAlias() as $key => $class) {
            $this->set($key, $this->compose("\\".$class));
        }

        $this->registerClassDefinition()
            ->setPropertyDefinition($this['definition.config']['property.definition']);

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
     * Application booting completed!
     * Lets run our awesome Application
     *
     * @return mixed
     * @throws \Exception
     */
    public function run()
    {
        /*
        | We will check if script running via console
        | then we will return out from here, else application
        | fall back down
         */
        if (isCli()) {
            return $this;
        }

        try {
            $response = $this->handle();

            if ($response instanceof ResponseInterface) {
                return $response->send();
            }

            return $response;

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

    /**
     * Throw an HttpException with the given message.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     */
    public function abort($code, $message = '', array $headers = array())
    {
        if ($code == 404) {
            throw new \Cygnite\Exception\Http\HttpNotFoundException($message);
        }

        throw new \Cygnite\Exception\Http\HttpException($code, $message, null, $headers);
    }
}
