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
use Cygnite\Reflection;
use Cygnite\Http\Requests\Request;
use Tracy\Helpers;
use Cygnite\Helpers\Config;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Translation\Translator;
use Cygnite\Translation\TranslatorInterface;
use Cygnite\Bootstrappers\Bootstrapper;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperDispatcherInterface;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Application implements ApplicationInterface
{
    /**
     * Store instance of the Application.
     *
     * @var instance
     */
    public static $instance;
    /**
     * The Cygnite Framework Version.
     *
     * @var string
     */
    const VERSION = 'v3.0';
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

    public $bootStrappers = [];

    protected $container;

    protected $bootstrappers;

    /**
     * ---------------------------------------------------
     * Application Constructor
     * ---------------------------------------------------
     * You cannot directly create object of Application
     * instance method will dynamically return you instance of
     * Application.
     *
     * @param \Cygnite\Container\ContainerAwareInterface $container
     * @param \Cygnite\Bootstrappers\BootstrapperDispatcherInterface $bootstrapper
     * @internal param $paths
     */
    public function __construct(ContainerAwareInterface $container, BootstrapperDispatcherInterface $bootstrapper)
    {
        $this->container = $container;
        $this->bootstrappers = $bootstrapper;
        $this->setPaths();
    }

    /**
     * Returns a Instance of Application either as Closure
     * or static instance.
     *
     * @param Closure $callback
     *
     * @param array $argument
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
     * ----------------------------------------------------.
     *
     * @param Autoloader $loader
     *
     * @return Application object
     */
    public static function getInstance($argument = [])
    {
        if (static::$instance instanceof self) {
            return static::$instance;
        }

        return static::$instance = new self($argument);
    }

    /**
     * @return array
     */
    public function getBootStrappers()
    {
        return $this->bootStrappers;
    }

    /**
     * Get the framework version.
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
        return 'Cygnite PHP Framework - '.static::version().' (<a href="http://www.cygniteframework.com">
            http://www.cygniteframework.com
            </a>)';
    }

    /**
     * Create an instance of the class and return it.
     *
     * @param $class
     * @param array $arguments
     *
     * @return mixed
     */
    public function compose(string $class, array $arguments = [])
    {
        return $this->container->makeInstance($class, $arguments);
    }

    /**
     * Resolve namespace via container.
     *
     * return @object
     */
    public function resolve(string $class, array $arguments = [])
    {
        return $this->container->resolve($class, $arguments = []);
    }

    /**
     * Import files using import function.
     *
     * @param $path
     * @return bool
     */
    public static function import($path)
    {
        return (new AutoLoader())->import($path);
    }

    public function getAliases($key)
    {
        return isset($this->aliases) ? $this->aliases : null;
    }

    /**
     * Return the translator instance
     *
     * @return static
     */
    public function getTranslator() : TranslatorInterface
    {
        return Translator::make();
    }

    /**
     * Set language to the translator.
     *
     * @param null $localization
     * @return locale
     */
    public function setLocale($localization = null)
    {
        $locale = Config::get('global.config', 'locale');
        if (!is_null($localization)) {
            $locale = $localization;
        }

        $fallbackLocale = Config::get('global.config', 'fallback.locale');
        $trans = $this->getTranslator();

        return $trans->setRootDirectory($this->container->get('app.path').DS.'Resources'.DS)
              ->setFallback($fallbackLocale)
              ->locale($locale);
    }

    /**
     * Execute all registered services.
     *
     */
    public function executeServices()
    {
        $serviceProvider = function () {
            $path = $this->container->get('app.config');
            extract(['app' => $this]);
            return include $path.DS.'services.php';
        };

        return $serviceProvider();
    }

    /**
     * Register directories for autoloader.
     *
     * @param $directories
     * @return mixed
     */
    public function registerDirectories($directories)
    {
        return (new AutoLoader())->registerDirectories($directories);
    }

    /**
     * We will register all service providers into application.
     *
     * @param array $services
     * @return $this
     */
    public function registerServiceProvider(array $services = []) : Application
    {
        foreach ($services as $key => $serviceProvider) {
            $this->createProvider($serviceProvider)->register($this->container);
        }

        return $this;
    }

    /**
     * Create a new provider instance.
     *
     * @param   $provider
     * @return mixed
     */
    public function createProvider($provider)
    {
        return new $provider($this->container);
    }

    /**
     * Set service controller.
     *
     * @param $key
     * @param $class
     * @return void
     */
    public function setServiceController($key, $class)
    {
        $this->container[$key] = function () use ($class) {
            $serviceController = $this->container->singleton(\Cygnite\Mvc\Controller\ServiceController::class);
            $instance = new $class($serviceController, $this->container);
            $serviceController->setController($class);

            return $instance;
        };
    }

    /**
     * Return Container Instance
     *
     * @return ContainerAwareInterface
     */
    public function getContainer() : ContainerAwareInterface
    {
        return $this->container;
    }

    /**
     * Create a Kernel and return it.
     *
     * @param $kernel
     * @return mixed
     */
    public function createKernel($kernel)
    {
        return new $kernel($this);
    }

    /**
     * Set Paths to Container.
     *
     * @return $this
     */
    public function setPaths() : ApplicationInterface
    {
        $this->container->set('app', $this);
        $paths = $this->bootstrappers->getBootstrapper()->getPaths();

        foreach ($paths->all() as $key => $path) {
            $this->container->set($key, $path);
        }

        return $this;
    }

    /**
     * Set all configurations and boot application.
     *
     * @return $this
     */
    public function bootApplication(Request $request) : Application
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
     * Indicate if Application booted or not.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @return $this
     */
    private function bootInternals()
    {
        if ($this->isBooted()) {
            return;
        }

        $this->registerCoreBootstrappers();
        $this->beforeBootingApplication();
        $this->executeServices();
        $this->afterBootingApplication();

        return $this;
    }

    /**
     * We will register all core class into container.
     *
     * @return instance ContainerAwareInterface
     */
    protected function registerCoreBootstrappers()
    {
        foreach ($this->getBootStrappers() as $key => $class) {
            $this->container->set($key, $this->compose('\\'.$class));
        }

        $this->bootstrappers->execute();

        return $this;
    }

    /**
     * We will activate middle ware events if set as true in
     * src/Apps/Configs/application.php.
     *
     * @return mixed
     */
    public function activateEventMiddleWare()
    {
        $isEventActive = Config::get('global.config', 'activate.event.middleware');
        $eventClass = Config::get('global.config', 'app.event.class');

        if ($isEventActive && !$this->container->has('event')) {
            $this->container->set('event', $this->container->make($eventClass));
            $this->container->get('event')->register($this->container);

            return true;
        }

        return false;
    }

    /**
     * Attach all application events to event handler.
     */
    public function attachEvents()
    {
        $appEvents = $this->container->get('event')->getAppEvents();

        if (!empty($appEvents)) {
            foreach ($appEvents as $event => $namespace) {
                // attach all before and after event to handler
                $this->container->get('event')->attach("$event", $namespace);
            }
        }
    }

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware.
     *
     * @return bool
     */
    public function beforeBootingApplication()
    {
        if (!$this->activateEventMiddleWare() ) {
            return false;
        }

        if ($this->container->get('event')->isAppEventEnabled() == false) {
            return true;
        }

        $this->attachEvents();

        return $this->container->get('event')->trigger('beforeBootingApplication', $this->container);
    }

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware.
     *
     * @return bool
     */
    public function afterBootingApplication()
    {
        if (!$this->activateEventMiddleWare() ) {
            return false;
        }

        if ($this->container->get('event')->isAppEventEnabled() == false) {
            return true;
        }
        return $this->container->get('event')->trigger('afterBootingApplication', $this->container);
    }

    /**
     * Throw an HttpException with the given message.
     *
     * @param int    $code
     * @param string $message
     * @param array  $headers
     * @throw \Cygnite\Exception\Http\HttpNotFoundException|Cygnite\Exception\Http\HttpException
     *
     * @return void
     */
    public function abort(int $code, string $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new \Cygnite\Exception\Http\HttpNotFoundException($message);
        }

        throw new \Cygnite\Exception\Http\HttpException($code, $message, null, $headers);
    }
}
