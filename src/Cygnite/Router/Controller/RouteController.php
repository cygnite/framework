<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Router\Controller;

use Cygnite\Exception\Http\HttpNotFoundException;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Inflector;
use Cygnite\Router\Router;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Trait RouteController.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class RouteController
{
    private $controllerWithNS;
    public $method;
    private $controller;

    public $dispatcher;
    public $controllerName;
    public $actionName;
    public $actionParams = [];
    public $app;
    public $route;
    protected $container;
    /**
     * @var string Application namespace
     */
    private $namespace = '\\Controllers\\';

    /**
     * Set container object.
     *
     * @param $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set or override namespace.
     *
     * @param $namespace
     */
    public function setControllerNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Get controller namespace.
     *
     * @return string
     */
    public function getControllerNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getUrlSegments()
    {
        $newUrl = str_replace('/' . Router::$indexPage, '', rtrim($this->getCurrentUri()));

        return array_filter(explode('/', $newUrl));
    }

    /**
     * @param $exp
     *
     * @return array
     */
    private function setControllerDirectoryConfig($exp)
    {
        return [ucfirst($exp[1]), $exp[2], $exp[3], array_slice($exp, 3)];
    }

    /**
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return object
     */
    public function callController($arguments)
    {
        $params = [];
        /*
        | Check if whether user trying to access module
        | If module we will setup module configuration
        | or else setup default MVC configurations
         */
        if (string_has($arguments[0], '::')) {
            $exp = string_split($arguments[0], '::');
            $this->setModuleConfiguration($exp);
        } else {
            $this->setUpControllerAndMethodName($arguments);
        }

        if (isset($arguments[1])) {
            $params = $arguments[1];
        }

        $args = (!is_array($params)) ? [$params] : $params;

        /*
        | Get the instance of controller from Cygnite Container
        | and inject all dependencies into controller dynamically.
        | It's cool! You can write powerful rest api using restful
        | routing
         */
        return $this->handleControllerDependencies($this->controllerWithNS, $this->method, $args);
    }

    /**
     * Set controller and method name here.
     *
     * @param $arguments
     */
    private function setUpControllerAndMethodName($arguments)
    {
        $expression = string_split($arguments[0], '@');
        $this->setControllerConfig($arguments, $expression);
    }

    /**
     * @param $args
     */
    private function setModuleConfiguration($args)
    {
        $param = string_split($args[1], '@');
        $this->bootstrapModule($args[0]);
        $this->setControllerConfig($args, $param, true);
    }

    /**
     * @param $module
     *
     * @return bool
     */
    public function bootstrapModule($module)
    {
        $config = Config::get('module');
        $modulePath = $config['module.path'] . DS;
        $moduleConfigDir = $config['module.config'] . DS;
        $class = '\\' . $this->getContainer()->get('app.namespace')
        . '\\' . $this->getModuleDir() . '\\' . ucfirst($module) . '\\BootStrap';

        $file = $modulePath . ucfirst($module) . DS . $moduleConfigDir . strtolower($module) . '.php';
        if (!file_exists($file)) {
            return false;
        }

        Config::set(strtolower($module) . '.config', include $file);

        return (new $class())->register($this->getContainer(), $file);
    }

    /**
     * @return mixed
     */
    public function getModuleDir()
    {
        return isset(static::$moduleDir) ? static::$moduleDir : static::MODULE_DIR;
    }

    /**
     * @param      $args
     * @param      $param
     * @param bool $module
     */
    private function setControllerConfig($args, $param, $module = false)
    {
        $this->controller = Inflector::classify($param[0]) . 'Controller';

        if ($module) {
            $this->namespace = '\\' . $this->getModuleDir() . '\\' . $args[0] . '\\Controllers\\';
        }
        $this->controllerWithNS = '\\' . $this->getContainer()->get('app.namespace')
        . $this->namespace . $this->controller;
        $this->method = Inflector::camelize($param[1]) . 'Action';
    }

    /**
     * Set router instance.
     *
     * @param $router
     *
     * @return $this
     */
    public function setRoute($router)
    {
        $this->route = $router;

        return $this;
    }

    /**
     * Get the router instance.
     *
     * @return mixed
     */
    public function getRouter()
    {
        return isset($this->route) ? $this->route : null;
    }

    /**
     * @param        $controller
     * @param        $action
     * @param string $controllerDir
     *
     * @return array
     */
    public function getControllerAndAction($controller, $action, $controllerDir = '')
    {
        $controller = $this->getControllerName($controller, $controllerDir);
        $action = $this->getActionName($action);

        return [$controller, $action];
    }

    /**
     * @param   $class
     * @param   $dir
     *
     * @return string
     */
    public function getControllerName($class, $dir = '')
    {
        $dir = ($dir !== '') ? $dir . '\\' : '';

        return '\\' . $this->getContainer()->get('app.namespace')
        . $this->namespace . $dir . Inflector::classify($class) . 'Controller';
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
     * @param       $controller
     * @param       $action
     * @param array $params
     *
     * @throws \Cygnite\Exception\Http\HttpNotFoundException
     * @return mixed
     */
    public function handleControllerDependencies($controller, $action, $params = [])
    {
        // make and return instance of controller
        $instance = $this->getContainer()->make($controller);
        $instance->initialize($this->getContainer());

        if (!method_exists($instance, $action)) {
            throw new HttpNotFoundException("Method $action() doesn't exists in $controller class.");
        }

        /** Auto resolve all method dependencies */
        $methodArgs = $this->getContainer()->resolveMethod($controller, $action);
        // Inject all properties of controller defined in definition file
        $this->getContainer()->propertyInjection($instance, $controller);
        // Trigger Before Action Events
        $this->triggerActionEvent($instance, $action);
        $response = call_user_func_array([$instance, $action], array_merge($methodArgs, $params));
        // Trigger After Action Events
        $this->triggerActionEvent($instance, $action, 'after');

        return $response;
    }

    /**
     * Trigger controller action events.
     *
     * @param        $instance
     * @param        $action
     * @param string $type
     */
    private function triggerActionEvent($instance, $action, $type = 'before')
    {
        if (method_exists($instance, $type . ucfirst($action))) {
            call_user_func([$instance, $type . ucfirst($action)]);
        }
    }
}
