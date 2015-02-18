<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base;

use Exception;
use Reflection;
use ErrorException;
use Cygnite\Foundation\Application as App;
use Cygnite\Helpers\Inflector;
use Cygnite\Helpers\Helper;

/*
 * Cygnite Router
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

class Router implements RouterInterface
{

    const MODULE_DIR = 'modules';
    /**
     * The current attributes being shared by routes.
     */
    public static $group;
    /**
     * @var base url
     */
    public $currentUrl;
    public $data = array();
    public $base;
    /**
     * The wildcard patterns supported by the router.
     *
     * @var array
     */
    public $patterns = array(
        '{:num}' => '([0-9]+)',
        '{:digit}' => '(\d+)',
        '{:name}' => '(\w+)',
        '{:any}' => '([a-zA-Z0-9\.\-_%]+)',
        '{:all}' => '(.*)',
        '{:module}' => '([a-zA-Z0-9_-]+)',
        '{:namespace}' => '([a-zA-Z0-9_-]+)',
        '{:year}' => '\d{4}',
        '{:month}' => '\d{2}',
        '{:day}' => '\d{2}(/[a-z0-9_-]+)'

    );
    protected $resourceRoutes = array('index', 'new', 'create', 'show', 'edit', 'update', 'delete');
    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = array();
    /**
     * @var array The before middle-ware route patterns and their handling functions
     */
    private $before = array();
    /**
     * @var object The function to be executed when no route has been matched
     */
    private $notFound;
    /**
     * @var string Application namespace
     */
    private $namespace = '\\Controllers\\';
    private $controller;
    /*
    * Available actions for resourceful controller
    * @var array
    */
    private $controllerWithNS;
    private $method;
    private $handledRoute;
    private $afterRouter;

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        if ($method == 'call') {
            return call_user_func_array(array(new self(), $method), $arguments);
        }
    }

    /**
     * Store a before middle-ware route and a handling function to be executed
     * when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     */
    public function before($methods, $pattern, $func)
    {
        $pattern = '/' . trim($pattern, '/');

        foreach (explode('|', $methods) as $method) {
            $this->before[$method][] = array(
                'pattern' => $pattern,
                'fn' => $func
            );
        }

    }

    public function setModuleDir($name)
    {
        static::$moduleDir = $name;
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return $this
     */
    public function __call($method, $arguments = array())
    {
        if ($method == 'call') {
            return $this->{$method . 'Controller'}($arguments);
        }
    }

    /**
     * Shorthand for a route accessed using GET
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function get($pattern, $func)
    {
        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function match($methods, $pattern, $func)
    {

        $pattern = '/' . trim($pattern, '/');

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = array(
                'pattern' => $pattern,
                'fn' => $func
            );
        }

        return true;
    }

    /**
     * Shorthand for a route accessed using POST
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function post($pattern, $func)
    {
        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using DELETE
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function delete($pattern, $func)
    {
        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using PUT
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function put($pattern, $func)
    {
        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using OPTIONS
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return bool
     */
    public function options($pattern, $func)
    {
        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * @param $pattern
     * @param $func
     * @return bool
     */
    public function any($pattern, $func)
    {
        return $this->match('GET|POST|PUT|DELETE', $pattern, $func);
    }

    /**
     * Set the controller as Resource Controller
     * Cygnite Router knows how to respond to resource controller
     * request automatically
     *
     * @param $name
     * @param $controller
     * @return $this
     */
    public function resource($name, $controller)
    {
        foreach ($this->resourceRoutes as $key => $action) {
            $this->{'setResource' . ucfirst($action)}($name, $controller, $action);
        }
        return $this;
    }

    /**
     * @return unknown
     */
    public function urlRoutes()
    {
        return (isset($this->routes[$_SERVER['REQUEST_METHOD']])) ?
            $this->routes[$_SERVER['REQUEST_METHOD']] :
            null;
    }

    /**
     * Execute the router: Loop all defined before middle-wares and routes,
     * and execute the handling function if a match was found
     *
     * @param object $callback Function to be executed after a matching
     *                         route was handled (= after router middle-ware)
     * @return mixed
     */
    public function run($callback = null)
    {
        //$this->afterRouter = !is_null($callback) ?: $callback;
        if (!is_null($callback) && $callback instanceof \Closure) {
            $this->afterRouter = $callback;
        }

        // Handle all before middle wares
        if (isset($this->before[$_SERVER['REQUEST_METHOD']])) {
            $this->handle($this->before[$_SERVER['REQUEST_METHOD']]);
        }

        // Handle all routes
        $numHandled = 0;
        if (isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            $flag = (!is_null($this->afterRouter)) ? true : false;
            $numHandled = $this->handle($this->routes[$_SERVER['REQUEST_METHOD']], $flag);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled == 0) {

            if (!is_null($this->notFound) && is_callable($this->notFound)) {
                return call_user_func($this->notFound);
            }
        }
    }

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     *
     * @param array   $routes       Collection of route patterns and their handling functions
     * @param boolean $quitAfterRun Does the handle function need to quit after one route was matched?
     * @param         $routes
     * @param bool    $quitAfterRun
     * @return int The number of routes handled
     */
    private function handle($routes, $quitAfterRun = false)
    {

        // Counter to keep track of the number of routes we've handled
        $numHandled = 0;

        //remove index.php and extra slash from url if exists to match with routing
        $uri = $this->removeIndexDotPhpAndTrillingSlash($this->getCurrentUri());

        // Loop all routes
        foreach ($routes as $route) {

            $routePattern = $this->hasNamedPattern($route['pattern']);
            $pattern = ($routePattern == false) ? $route['pattern'] : $routePattern;

            // we have a match!
            if (preg_match_all(
                '#^' . $pattern . '$#',
                $uri,
                $matches,
                PREG_SET_ORDER
            )
            ) {

                // Extract the matched URL (and only the parameters)
                $params = array_map(
                    function ($match) {
                        $var = explode('/', trim($match, '/'));
                        return isset($var[0]) ? $var[0] : null;
                    },
                    array_slice(
                        $matches[0],
                        1
                    )
                );
                array_unshift($params, $this);
                //show($params);
                // call the handling function with the URL
                $this->handledRoute = call_user_func_array($route['fn'], $params);

                $numHandled++;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    $func = $this->afterRouter;
                    // If a route was handled, perform the finish callback (if any)
                    $func($this);
                    exit;
                }
            }
        }

        // Return the number of routes handled
        return $numHandled;

    }

    /**
     * @param $uri
     * @return mixed|string
     */
    public function removeIndexDotPhpAndTrillingSlash($uri)
    {
        return (strpos($uri, 'index.php') !== false) ?
            preg_replace(
                '/(\/+)/',
                '/',
                str_replace('index.php', '', rtrim($uri))
            ) :
            trim($uri);
    }

    /**
     * Define the current relative URI
     *
     * @return string
     */
    public function getCurrentUri()
    {
        $basePath = $this->getBaseUrl();
        $uri = $this->currentUrl;

        $this->base = $basePath;
        $uri = substr($uri, strlen($basePath));

        // Don't take query params into account on the URL
        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * Get the base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        // Current Request URI
        $this->currentUrl = $_SERVER['REQUEST_URI'];

        // Remove rewrite base path (= allows one to run the router in a sub folder)
        $basePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';

        return $basePath;

    }

    /**
     * @param $pattern
     * @return bool|mixed
     */
    public function hasNamedPattern($pattern)
    {
        return (Helper::strHas($pattern, '{:') !== false) ? $this->replace($pattern) : false;
    }

    /**
     * @param $string
     * @return mixed
     */
    private function replace($string)
    {
        foreach ($this->patterns as $key => $value) {
            $string = str_replace($key, $value, $string);
        }

        return $string;
    }

    /**
     * Set the 404 handling function
     *
     * @param object $func The function to be executed
     */
    public function set404($func)
    {
        $this->notFound = $func;
    }

    /**
     * @param          $attributes
     * @param callable $callback
     */
    public function group($attributes, \Closure $callback)
    {
        static::$group = $attributes;

        call_user_func($callback($this));

        static::$group = null;
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceIndex($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('get'),
            $name,
            function () use ($me, $controller, $action) {
                $args = array($controller . '.' . $action);
                return $me->callController($args);
            }
        );
    }

    /**
     * @param $arguments
     * @return object
     * @throws \Exception
     */
    private function callController($arguments)
    {
        $params = array();
        $this->setUpControllerAndMethodName($arguments);

        // Check if whether user trying to access module
        if (Helper::strHas($arguments[0], '::')) {
            $exp = Helper::stringSplit($arguments[0], '::');
            $this->setModuleConfiguration($exp);
        }

        if (isset($arguments[1])) {
            $params = $arguments[1];
        }

        $file = CYGNITE_BASE . str_replace('\\', DS, $this->controllerWithNS) . EXT;

        if (!is_readable($file)) {
            throw new \Exception("Route " . $this->controllerWithNS . " not found. ");
        }

        $me = $this;
        // Get the instance of controller from Cygnite Container
        // and inject all dependencies into controller dynamically
        // It's cool. You can write powerful rest api using restful
        // routing
        return App::instance(
            function ($app) use ($me, $params) {
                // make and return instance of controller
                $instance = $app->make($me->controllerWithNS);
                // inject all properties of controller defined in definition
                $app->propertyInjection($instance, $me->controllerWithNS);
                return call_user_func_array(array($instance, $me->method), array($params));
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
        $expression = Helper::stringSplit($arguments[0]);
        $this->controller = Inflector::instance()->classify($expression[0]) . 'Controller';
        $this->controllerWithNS = "\\" . ucfirst(APPPATH) . $this->namespace . $this->controller;
        $this->method = Inflector::instance()->toCameCase($expression[1]) . 'Action';
    }

    private function setModuleConfiguration($args)
    {
        $param = Helper::stringSplit($args[1]);
        $this->controller = Inflector::instance()->classify($param[0]) . 'Controller';
        $this->namespace = '\\' . ucfirst($this->getModuleDir()) . '\\' . $args[0] . '\\Controllers\\';
        $this->controllerWithNS = "\\" . ucfirst(APPPATH) . $this->namespace . $this->controller;
        $this->method = Inflector::instance()->toCameCase($param[1]) . 'Action';
    }

    public function getModuleDir()
    {
        return isset(static::$moduleDir) ? static::$moduleDir : static::MODULE_DIR;
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceNew($name, $controller, $action, $options = array())
    {
        return $this->mapResource('get', $name . '/' . $action, $controller, $action);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceCreate($name, $controller, $action, $options = array())
    {
        return $this->mapResource('post', $name, $controller, $action);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceShow($name, $controller, $action, $options = array())
    {
        return $this->mapResource('get', $name . '/(\d+)', $controller, $action, true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceEdit($name, $controller, $action, $options = array())
    {
        return $this->mapResource('get', $name . '/(\d+)/edit', $controller, $action, true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceUpdate($name, $controller, $action, $options = array())
    {
        return $this->mapResource('PUT|PATCH', $name . '/(\d+)/', $controller, $action, true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceDelete($name, $controller, $action, $options = array())
    {
        return $this->mapResource('delete', $name . '/(\d+)/', $controller, $action, true);
    }

    /**
     * @param      $method
     * @param      $pattern
     * @param      $controller
     * @param      $action
     * @param bool $type
     * @return bool
     */
    private function mapResource($method, $pattern, $controller, $action, $type = false)
    {
        $me = $this;
        return $this->match(
            strtoupper($method),
            $pattern,
            function ($router, $id) use ($me, $controller, $action, $type) {

                $args = array($controller . '.' . $action);
                if ($type) {
                    $args = array($controller . '.' . $action, $id);// delete, update
                }
                return $me->callController($args);
            }
        );
    }

    private function getCalledRouter()
    {

    }
}
