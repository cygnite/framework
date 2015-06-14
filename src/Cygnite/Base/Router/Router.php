<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base\Router;

use Exception;
use Reflection;
use ErrorException;
use Cygnite\Helpers\Inflector;
use Cygnite\Helpers\Helper;
use Cygnite\Foundation\Application as App;
use Cygnite\Base\Router\Controller\Controller;
use Cygnite\Base\Router\Controller\RouteControllerTrait;
use Cygnite\Base\Router\Controller\ResourceControllerTrait;

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
    use RouteControllerTrait, ResourceControllerTrait;

    const MODULE_DIR = 'Modules';
    /**
     * The current attributes being shared by routes.
     */
    public static $group;
    /**
     * @var base url
     */
    public $currentUrl;
    public $data = [];
    public $base;
    /**
     * The wildcard patterns supported by the router.
     *
     * @var array
     */
    public $patterns = array(
        '{:num}' => '([0-9]+)',
        '{:id}' => '(\d+)',
        '{:name}' => '(\w+)',
        '{:any}' => '([a-zA-Z0-9\.\-_%]+)',
        '{:all}' => '(.*)',
        '{:module}' => '([a-zA-Z0-9_-]+)',
        '{:namespace}' => '([a-zA-Z0-9_-]+)',
        '{:year}' => '\d{4}',
        '{:month}' => '\d{2}',
        '{:day}' => '\d{2}(/[a-z0-9_-]+)'
    );

    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = [];
    /**
     * @var array The before middle-ware route patterns and their handling functions
     */
    private $before = [];
    /**
     * @var object The function to be executed when no route has been matched
     */
    private $notFound;
    /**
     * @var string Application namespace
     */
    private $namespace = '\\Controllers\\';

    private $handledRoute;
    private $afterRouter;
    // route base path
    private $routeBasePath = '';
    private $after = [];

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        if ($method == 'call') {
            return call_user_func_array([new self(), $method], $arguments);
        }
    }

    /**
     * Store a before middle-ware route and a handling function to be executed
     * when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     * @return mixed|void
     */
    public function before($methods, $pattern, $func)
    {
        $pattern = $this->setBaseRoute($pattern);

        foreach (explode('|', $methods) as $method) {
            $this->before[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }
    }

    /**
     * @param $func
     */
    public function after($func)
    {
        $pattern = $this->setBaseRoute('{:all}');
        foreach (explode('|', 'GET|POST|PUT|PATCH|DELETE') as $method) {
            $this->after[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }
    }

    /**
     * @param $pattern
     * @return string
     */
    private function setBaseRoute($pattern)
    {
        $pattern = $this->routeBasePath . '/' . trim($pattern, '/');

        return $this->routeBasePath ? rtrim($pattern, '/') : $pattern;
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
    public function __call($method, $arguments = [])
    {
        if ($method == 'call') {
            return $this->{$method . 'Controller'}($arguments);
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
        $pattern = $this->setBaseRoute($pattern);

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }

        return $this;
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
        if (!$func instanceof \Closure) {

            /**
             * We will bind static routes to callable
             * closure object
             * @return object
             */
            $callable = function () use ($func) {
                return $this->callStaticRoute($func);
            };

            return $this->match(strtoupper(__FUNCTION__), $pattern, $callable);
        }

        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * @param $uri
     * @return object
     */
    public function callStaticRoute($uri)
    {
        $params = array_slice($this->getUrlSegments(), 2);

        return $this->callController([$uri, $params]);
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
     * Shorthand for route accessed using patch
     * @param $pattern
     * @param $func
     * @return bool
     */
    public function patch($pattern, $func)
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
        return $this->match('GET|POST|PUT|PATCH|DELETE', $pattern, $func);
    }

     /**
     * Customize the routing pattern using where
     *
     * @param $key
     * @param $pattern
     * @return $this
     */
    public function where($key, $pattern)
    {
        return $this->pattern($key, $pattern);
    }

    /**
     * @param $key
     * @param $pattern
     * @return $this
     */
    private function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;

        return $this;
    }

    /**
     * @param $key
     * @return string
     */
    public function getPattern($key)
    {
        return isset($this->patterns[$key]) ? $this->patterns[$key] : '';
    }

    /**
     * Allow you to apply nested sub routing.
     *
     * @param          $groupRoute
     * @param callable $callback
     */
    public function group($groupRoute, \Closure $callback)
    {
        // Track current base path
         $curBaseRoute = $this->routeBasePath;
        // Build new route base path string
        $this->routeBasePath .= $groupRoute;

        // Call the Closure callback
        call_user_func(function() use($callback)
        {
            return $callback($this);
        });

        // Restore original route base path
       $this->routeBasePath = $curBaseRoute;
    }

    public function getRouteControllerInstance()
    {
        $this->setRouter($this);

        return $this;
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
        return $this->resourceController($this, $name, $controller);
    }

    /**
     * @param $controllerName
     * @return mixed
     */
    public function routeController($controllerName)
    {
        $app = App::instance();
        return (new Controller)->{__FUNCTION__}($controllerName);
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
     * and call function to handle request if any matching pattern found
     *
     * @param null $callback
     * @return mixed
     */
    public function run($callback = null)
    {
        // Check before routing middle ware and trigger
        $this->beforeRoutingMiddleware();
        // Set after routing event
        $this->setAfterRoutingMiddleWare();

        // Handle all routes
        $handledRequest = 0;
        if (isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            $flag = (!is_null($this->afterRouter)) ? true : false;
            $handledRequest = $this->handle($this->routes[$_SERVER['REQUEST_METHOD']], $flag);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($handledRequest == 0) {

            if (!is_null($this->notFound) && is_callable($this->notFound)) {
                return call_user_func($this->notFound);
            }
        }
    }

    private function beforeRoutingMiddleWare()
    {
        // Handle all before middle wares
        if (isset($this->before[$_SERVER['REQUEST_METHOD']])) {
            $this->handle($this->before[$_SERVER['REQUEST_METHOD']]);
        }
    }

    private function setAfterRoutingMiddleWare()
    {
        if (isset($this->after[$_SERVER['REQUEST_METHOD']])) {
            $this->afterRouter = $this->after[$_SERVER['REQUEST_METHOD']];
        }
    }

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     *
     * @param         $routes Collection of route patterns
     * @param bool    $fireAfterRoutingCallback
     * @return int The number of routes handled
     */
    private function handle($routes, $fireAfterRoutingCallback = false)
    {
        // Counter to keep track of the number of routes we've handled
        $handledRequest = 0;

        //remove index.php and extra slash from url if exists to match with routing
        $uri = $this->removeIndexDotPhpAndTrillingSlash($this->getCurrentUri());

        $i = 0;
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
                $params = $this->extractParams($matches);
                array_unshift($params, $this);

                // call the handling function with the URL
                $this->handledRoute = call_user_func_array($route['fn'], $params);

                $handledRequest++;

                // If we need to quit, then quit
                if ($fireAfterRoutingCallback) {
                    // If a route was handled, perform the finish callback (if any)
                   $this->handle($this->afterRouter);
                }
            }
            $i++;
        }

        // Return the number of routes handled
        return $handledRequest;
    }

    /**
     * @param $matches
     * @return array
     */
    private function extractParams($matches)
    {
        return array_map(
            function ($match) {
                $args = explode('/', trim($match, '/'));
                return isset($args[0]) ? $args[0] : null;
            },
            array_slice(
                $matches[0],
                1
            )
        );
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
        return (string_has($pattern, '{:')) ? $this->replace($pattern) : false;
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
     * @return mixed|void
     */
    public function set404Page($func)
    {
        $this->notFound = $func;
    }
}
