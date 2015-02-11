<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base;

/**
 * Cygnite Router
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

use Cygnite\Foundation\Application;
use Cygnite\Helpers\Helper;
use Cygnite\Helpers\Inflector;
use Exception;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

class Router implements RouterInterface
{
    /**
     * @var base url
     */
    public $currentUrl;
    public $data = array();
    public $base;
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
    private $controllerWithNS;
    private $method;
    private $handledRoute;
    /*
	* Available actions for resourceful controller
	* @var array
	*/
    protected $resourceRoutes = array('index', 'new', 'create', 'show', 'edit', 'update', 'delete');

    private $afterRouter;

    /**
     * Store a before middle-ware route and a handling function to be executed
     * when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /admin/system
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
     * We will handle any request either GET OR POST
     * @param $pattern
     * @param $func
     * @return bool
     */
    public function any($pattern, $func)
    {
        return $this->match('GET|POST', $pattern, $func);
    }

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
     */
    public function run($callback = null)
    {
        $this->afterRouter = is_null($callback) ?: $callback;

        // Handle all before middle wares
        if (isset($this->before[$_SERVER['REQUEST_METHOD']])) {
            $this->handle($this->before[$_SERVER['REQUEST_METHOD']]);
        }

        // Handle all routes
        $numHandled = 0;
        if (isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            $numHandled = $this->handle($this->routes[$_SERVER['REQUEST_METHOD']], true);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled == 0) {

            if (!is_null($this->notFound) && is_callable($this->notFound)) {
                call_user_func($this->notFound);
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

            // we have a match!
            if (preg_match_all(
                '#^' . $route['pattern'] . '$#',
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

                // call the handling function with the URL
                $this->handledRoute = call_user_func_array($route['fn'], $params);

                $numHandled++;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    $callback = $this->afterRouter;
                    // If a route was handled, perform the finish callback (if any)
                    $callback ($this);
                    exit;
                }
            }
        }

        // Return the number of routes handled
        return $numHandled;
    }

    /**
     * @param $arguments
     * @return object
     * @throws \Exception
     */
    private function callController($arguments)
    {
        $params = array();
        if (isset($arguments[1])) {
            $params = $arguments[1];
        }

        $this->setUpControllerAndMethodName($arguments);

        $file = (CYGNITE_BASE . strtolower(
                str_replace('\\', DS, "\\" . ucfirst(APPPATH) . $this->namespace)
            ) . $this->controller . EXT);

        if (!is_readable($file)) {
            throw new \Exception("Route " . array_pop($arguments) . " not found. ");
        }

        //include $file;
        $router = $this;
        // Get the instance of controller from Cygnite Container
        // and inject all dependencies into controller dynamically
        // It's cool. You can write powerful rest api using restful
        // routing
        return Application::instance(
            function ($app) use ($router, $params) {
                // make and return instance of controller
                $instance = $app->make($router->controllerWithNS);
                // inject all properties of controller defined in definition
                $app->propertyInjection($instance, $router->controllerWithNS);
                return $instance->{$router->method}($params);
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

    protected function setResourceIndex($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('get'),
            $name,
            function () use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action));
            }
        );
    }

    protected function setResourceNew($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('get'),
            $name . '/' . $action,
            function () use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action));
            }
        );
    }

    protected function setResourceCreate($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('post'),
            $name,
            function () use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action));
            }
        );
    }

    protected function setResourceShow($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('get'),
            $name . '/(\d+)',
            function ($router, $id) use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action, $id));
            }
        );
    }

    protected function setResourceEdit($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('get'),
            $name . '/(\d+)/edit',
            function ($router, $id) use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action, $id));
            }
        );
    }

    protected function setResourceUpdate($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            'PUT|PATCH',
            $name . '/(\d+)/',
            function ($router, $id) use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action, $id));
            }
        );
    }

    protected function setResourceDelete($name, $controller, $action, $options = array())
    {
        $me = $this;
        return $this->match(
            strtoupper('delete'),
            $name . '/(\d+)/',
            function ($router, $id) use ($me, $controller, $action) {
                return $me->callController(array($controller . '.' . $action, $id));
            }
        );
    }

    public function getCalledRouter()
    {

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
     * Set the 404 handling function
     *
     * @param object $func The function to be executed
     */
    public function set404($func)
    {
        $this->notFound = $func;
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        if ($method == 'call') {
            return call_user_func_array(
                array(new Router(), $method),
                $arguments
            );
        }
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
}
