<?php
namespace Cygnite\Base;

use Exception;
use Reflection;
use ErrorException;
use Cygnite\Application;
use Cygnite\Inflector;
use Cygnite\Helpers\Helper;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package            :  Packages
 * @Sub Packages       :  Base
 * @Filename           :  Router
 * @Description        :  This file is used to route user requests.
 *                        This class is highly inspired and few code borrowed
 *                        from Barmus Router.
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0
 *
 *
 */
/**
* @author	Bram(us) Van Damme
* @author   Sanjoy Dey
*/

class Router implements RouterInterface
{
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
     * @var base url
     */
	public $currentUrl;

	public $data = array();

    /**
     * @var string Application namespace
     */
    private $namespace = '\\Apps\\Controllers\\';

    private $controller;

    private $controllerWithNS;

    private $method;

    public $base;


    /**
     * Store a before middle-ware route and a handling function to be executed
     * when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param object $func The handling function to be executed
     */
    public function before($methods, $pattern, $func)
    {
        $pattern = '/' .trim($pattern, '/');

        foreach (explode('|', $methods) as $method) {
            $this->before[$method][] = array(
                'pattern' => $pattern,
                'fn' => $func
            );
        }

    }

    /**
     * Set controller and method name here
     * @param $arguments
     */
    private function setUpControllerAndMethodName($arguments)
    {
        $expression = Helper::stringSplit($arguments[0]);
        $this->controller = Inflector::instance()->covertAsClassName($expression[0]).'Controller';
        $this->controllerWithNS = $this->namespace.$this->controller;
        $this->method = Inflector::instance()->toCameCase($expression[1]).'Action';

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

        if ($method == 'instance') {

            if (self::$instance === null) {
                self::$instance = new self();
            }

            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }

        if ($method == 'end') { exit;}
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return $this
     */
    public function __call($method, $arguments = array())
    {
        if ($method == 'instance') {
            return $this;
        }

        if ($method == 'call') {
            return $this->{$method.'Controller'}($arguments);
        }
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

        $file = (CYGNITE_BASE.strtolower(
                str_replace('\\', DS, $this->namespace)
            ).$this->controller.EXT);

        if (!is_readable($file)) {
            throw new \Exception("Route ".array_pop($arguments)." not found. ");
        }

        //include $file;
        $router = $this;
        // Get the instance of controller from Cygnite Container
        // and inject all dependencies into controller dynamically
        // It's cool. You can write powerful rest api using restful
        // routing
        return Application::instance(
            function($app) use($router)
            {
                // make and return instance of controller
                $instance = $app->make($router->controllerWithNS);
                // inject all properties of controller defined in definition
                $app->propertyInjection($instance, $router->controllerWithNS);
                return $instance->{$router->method}();
            }
        );
    }

    private function getCalledRouter()
    {

    }

    /**
    * Store a route and a handling function to be executed when accessed using one of the specified methods
    *
    * @param string $methods Allowed methods, | delimited
    * @param string $pattern A route pattern such as /about/system
    * @param object $func The handling function to be executed
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

    }


    /**
    * Shorthand for a route accessed using GET
    *
    * @param string $pattern A route pattern such as /about/system
    * @param object $func The handling function to be executed
    */
    public function get($pattern, $func)
    {
        $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }


    /**
     * Shorthand for a route accessed using POST
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func The handling function to be executed
     */
    public function post($pattern, $func)
    {
        $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }


    /**
     * Shorthand for a route accessed using DELETE
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func The handling function to be executed
     */
    public function delete($pattern, $func)
    {
        $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }


    /**
     * Shorthand for a route accessed using PUT
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func The handling function to be executed
     */
    public function put($pattern, $func)
    {
        $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }


    /**
     * Shorthand for a route accessed using OPTIONS
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func The handling function to be executed
     */
    public function options($pattern, $func)
    {
        $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * @return unknown
     */
    public function urlRoutes()
    {
        return (isset($this->routes[$_SERVER['REQUEST_METHOD']] )) ?
            $this->routes[$_SERVER['REQUEST_METHOD']] :
            null;
    }


    /**
     * Execute the router: Loop all defined before middle-wares and routes,
     * and execute the handling function if a match was found
     *
     * @param object $callback Function to be executed after a matching
     * route was handled (= after router middle-ware)
     */
    public function run($callback = null)
    {

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
            /*else {
                if (!headers_sent()) {
                    //header('HTTP/1.1 404 Not Found');
                }
            }*/
        } else {
            // If a route was handled, perform the finish callback (if any)
            if ($callback) {
                $callback();
            }
        }
    }


    /**
    * Set the 404 handling function
    * @param object $func The function to be executed
    */
    public function set404($func)
    {
        $this->notFound = $func;
    }

    /**
     * @param $uri
     * @return mixed|string
     */
    public function removeIndexDotPhpAndTrillingSlash($uri)
	{
			return  (strpos($uri,'index.php') !== false) ?
                preg_replace(
                    '/(\/+)/','/', str_replace('index.php', '', rtrim($uri))
                ) :
                trim($uri);
	}

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     *
     * @param array $routes Collection of route patterns and their handling functions
     * @param boolean $quitAfterRun Does the handle function need to quit after one route was matched?
     * @param      $routes
     * @param bool $quitAfterRun
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
            if ( preg_match_all(
                '#^' . $route['pattern'] . '$#',
                $uri,
                $matches,
                PREG_SET_ORDER)
            ) {

                // Extract the matched URL (and only the falseeters)
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
                // call the handling function with the URL
                call_user_func_array($route['fn'], $params);

                // yay!
                $numHandled++;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }
            }
        }

        // Return the number of routes handled
        return $numHandled;

    }

	public function getBaseUrl()
	{
		// Current Request URI
        $this->currentUrl = $_SERVER['REQUEST_URI'];

        // Remove rewrite base path (= allows one to run the router in a sub folder)
        $basePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';

		return $basePath;

	}

    /**
     * Define the current relative URI
     * @return string
     */
    public function getCurrentUri()
    {
		$uri = $this->currentUrl;
		$basePath = $this->getbaseUrl ();

		 $this->base = 	$basePath;
        $uri = substr($uri, strlen($basePath));

        // Don't take query params into account on the URL
        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
         $uri = '/' . trim($uri, '/');

        return $uri;

    }
}
