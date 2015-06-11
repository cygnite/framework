<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base\Router\Controller;

use Exception;
use Reflection;
use Cygnite\Foundation\Application as App;
use Cygnite\Base\Router\Router;
use Cygnite\Helpers\Inflector;
use Cygnite\Helpers\Helper;
use Cygnite\Exception\Http\HttpNotFoundException;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Trait RouteController
 *
 * @package Cygnite\Base\Router\Controller
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

trait RouteController
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

    public function findControllerAndMethodStaticRoute($route)
    {
        return explode('.', $route);
    }

    public function getUrlSegments()
    {
        $newUrl = str_replace('/index.php', '', rtrim($this->getCurrentUri()));
        return array_filter(explode('/', $newUrl));
    }

    public function getControllerByUrlSegment()
    {
        $exp = $this->getUrlSegments();
        $controller = $params = null;$controllerDir = "";
        $controller = $exp[1];
        $params = array_slice($exp, 2);
        $dir = CYGNITE_BASE.str_replace('\\', DS, strtolower("\\" .APPPATH.$this->getApplication()->namespace.$exp[1]));

        /*
         | We will check if user requesting uri has directory
         | inside, then we will consider next param as controller name
         | /admin/user/index/32
         | directory    : admin
         | controller   : user
         | action       : index
         | param        : 32
         */
        if (is_dir($dir)) {
            list($controllerDir, $controller, $method, $params) = $this->setControllerDirectoryConfig($exp);
        }

        $method = (isset($exp[2])) ? $exp[2] : null;
        $action = (isset($method)) ? $method : 'index';
        /*
         | Make Controller and Action name and return
         */
        list($controller, $action) = $this->getControllerAndAction($controller, $action, $controllerDir);

        $this->controllerName = $controller;
        $this->actionName = $action;
        $this->actionParams = $params;
    }

    private function setControllerDirectoryConfig($exp)
    {
        return [ucfirst($exp[1]), $exp[2], $exp[3], array_slice($exp, 3)];
    }

    /**
     * @param $arguments
     * @return object
     * @throws \Exception
     */
    public function callController($arguments)
    {
        $params = [];
        $this->setUpControllerAndMethodName($arguments);

        // Check if whether user trying to access module
        if (string_has($arguments[0], '::')) {
            $exp = string_split($arguments[0], '::');
            $this->setModuleConfiguration($exp);
        }

        if (isset($arguments[1])) {
            $params = $arguments[1];
        }

        $file = CYGNITE_BASE . str_replace('\\', DS, '\\src\\'.$this->controllerWithNS) . EXT;

        if (!is_readable($file)) {
            throw new \Exception("Route " . $this->controllerWithNS . " not found. ");
        }

        // Get the instance of controller from Cygnite Container
        // and inject all dependencies into controller dynamically
        // It's cool. You can write powerful rest api using restful
        // routing
        return App::instance(
            function ($app) use ($params) {
                // make and return instance of controller
                $instance = $app->make($this->controllerWithNS);
                // inject all properties of controller defined in definition
                $app->propertyInjection($instance, $this->controllerWithNS);
                $args = [];
                $args = (!is_array($params)) ? [$params] : $params;
                return call_user_func_array([$instance, $this->method], $args);
            }
        );
    }

    /**
     * Set controller and method name here
     *
     * @param $arguments
     */
    private function setUpControllerAndMethodName($arguments)
    {
        $expression = string_split($arguments[0]);
        $this->setControllerConfig($arguments, $expression);
    }

    private function setModuleConfiguration($args)
    {
        $param = string_split($args[1]);
        $this->setControllerConfig($args, $param, true);
    }

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
            $this->namespace = '\\' . ucfirst($this->getModuleDir()) . '\\' . $args[0] . '\\Controllers\\';
        }
        $this->controllerWithNS = "\\" . str_replace('src/', '', APPPATH) . $this->namespace . $this->controller;
        $this->method = Inflector::camelize($param[1]) . 'Action';
    }


    private function getApplication()
    {
        return App::instance();
    }

    public function setRoute($router)
    {
        $this->route = $router;

        return $this;
    }

    public function getRouter()
    {
        return isset($this->route) ? $this->route : null;
    }

    /**
     * @param        $controller
     * @param        $action
     * @param string $controllerDir
     * @return array
     */
    public function getControllerAndAction($controller, $action, $controllerDir = '')
    {
        $controller = $this->getControllerName($controller, $controllerDir);
        $action = $this->getActionName($action);

        return [$controller, $action];
    }

    /**
     * @param      $class
     * @param      $dir
     * @return string
     */
    public function getControllerName($class, $dir = '')
    {
        $dir = ($dir !== '') ? $dir . '\\' : '';

        return
            "\\" . str_replace('src/', '', APPPATH) . $this->namespace . $dir .
            Inflector::classify(
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
     * @param       $controller
     * @param       $action
     * @param array $params
     * @return mixed
     */
    public function handleControllerDependencies($controller, $action, $params = [])
    {
        $instance = $this->getApplication()->make($controller);

        if (!method_exists($instance, $action)) {
            throw new HttpNotFoundException("Unhandled Exception: Controller Action $controller::$action() Not Found!");

            return (int) 0;
        }

        $this->getApplication()->propertyInjection($instance, $controller);
        // Trigger Events for Action
        $this->triggerActionEvent($instance, $action);
        $response = call_user_func_array([$instance, $action], $params);
        $this->triggerActionEvent($instance, $action, 'after');

        return $response;
    }

    /**
     * Trigger controller action events
     *
     * @param        $instance
     * @param        $action
     * @param string $type
     */
    private function triggerActionEvent($instance, $action, $type = 'before')
    {
        if (method_exists($instance, $type.ucfirst($action))) {
            call_user_func([$instance, $type.ucfirst($action)]);
        }
    }
}
