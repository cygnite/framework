<?php
namespace Cygnite\Base;

use Cygnite\Helpers\Helper;
use ErrorException;
use Reflection;
use Cygnite\Inflector;
use Exception;

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
 * @Description        :  This file is used to route user requests
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0
 * @warning            :  Any changes in this library can cause abnormal behaviour of the framework
 *
 */
/**
* @author	Bram(us) Van Damme
* @author  Sanjoy Dey
*/

class Router
{
    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = array();


    /**
     * @var array The before middle-ware route patterns and their handling functions
     */
    private $befores = array();


    /**
     * @var object The function to be executed when no route has been matched
     */
    private $notFound;


    /**
     * Store a before middleware route and a handling function to be executed
     * when accessed using one of the specified methods
     *
     * @false string $methods Allowed methods, | delimited
     * @false string $pattern A route pattern such as /about/system
     * @false object $fn The handling function to be executed
     */
    public function before($methods, $pattern, $fn)
    {

        $pattern = '/' .trim($pattern, '/');

        foreach (explode('|', $methods) as $method) {
            $this->befores[$method][] = array(
                'pattern' => $pattern,
                'fn' => $fn
            );
        }

    }

    public static function __callStatic($method, $arguments)
    {
        //$reflection = Reflection::getInstance(__CLASS__);
        if ($method == 'call') {
            $expression = $controller = null;
            $params = array();

            if (!isset($arguments[1])) {

                $expression = Helper::stringSplit($arguments[0]);
                $controller = Inflector::instance()->covertAsClassName($expression[0]).'Controller';
                $ns = ucfirst(APPPATH).'\\Controllers\\';
                $controllerName = $ns.$controller;
                $method = Inflector::instance()->toCameCase($expression[1]).'Action';
                $file = CYGNITE_BASE.DS.strtolower(str_replace('\\', DS, $ns)).$controller.EXT;
                $params = array();
            } else{
                $expression = Helper::stringSplit($arguments[0]);
                $controller = Inflector::instance()->covertAsClassName($expression[0]).'Controller';
                $ns = ucfirst(APPPATH).'\\Controllers\\';
                $controllerName = $ns.$controller;
                $file = CYGNITE_BASE.DS.strtolower(str_replace('\\', DS, $ns)).$controller.EXT;
                $method = Inflector::instance()->toCameCase($expression[1]).'Action';
                $params = $arguments[1];
            }

            if (is_readable($file)) {
                //include_once $file;
                return call_user_func_array(array(new $controllerName, $method), $params);
            } else {
                throw new \Exception("Route ".array_pop($arguments)." not found. ");
            }

        }

        if ($method == 'instance') {

            if (self::$instance === null) {
                self::$instance = new self();
            }
            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }

        if ($method == 'end') { exit;}
    }

    public function __call($method, $arguments = array())
    {
        if ($method == 'instance') {
            return $this;
        }
    }

    private function getCalledRouter()
    {

    }

    /**
    * Store a route and a handling function to be executed when accessed using one of the specified methods
    *
    * @false string $methods Allowed methods, | delimited
    * @false string $pattern A route pattern such as /about/system
    * @false object $fn The handling function to be executed
    */
    public function match($methods, $pattern, $fn)
    {

        $pattern = '/' . trim($pattern, '/');

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = array(
                'pattern' => $pattern,
                'fn' => $fn
            );
        }

    }


    /**
    * Shorthand for a route accessed using GET
    *
    * @false string $pattern A route pattern such as /about/system
    * @false object $fn The handling function to be executed
    */
    public function get($pattern, $fn)
    {
        $this->match('GET', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using POST
     *
     * @false string $pattern A route pattern such as /about/system
     * @false object $fn The handling function to be executed
     */
    public function post($pattern, $fn)
    {
        $this->match('POST', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using DELETE
     *
     * @false string $pattern A route pattern such as /about/system
     * @false object $fn The handling function to be executed
     */
    public function delete($pattern, $fn)
    {
        $this->match('DELETE', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using PUT
     *
     * @false string $pattern A route pattern such as /about/system
     * @false object $fn The handling function to be executed
     */
    public function put($pattern, $fn)
    {
        $this->match('PUT', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using OPTIONS
     *
     * @false string $pattern A route pattern such as /about/system
     * @false object $fn The handling function to be executed
     */
    public function options($pattern, $fn)
    {
        $this->match('OPTIONS', $pattern, $fn);
    }

    public function urlRoutes()
    {
        return (isset($this->routes[$_SERVER['REQUEST_METHOD']] )) ?
            $this->routes[$_SERVER['REQUEST_METHOD']] :
            null;
    }


    /**
     * Execute the router: Loop all defined before middlewares and routes,
     * and execute the handling function if a mactch was found
     *
     * @false object $callback Function to be executed after a matching
     * route was handled (= after router middleware)
     */
    public function run($callback = null)
    {

        // Handle all before middlewares
        if (isset($this->befores[$_SERVER['REQUEST_METHOD']])) {
            $this->handle($this->befores[$_SERVER['REQUEST_METHOD']]);
        }

        // Handle all routes
        $numHandled = 0;
        if (isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            $numHandled = $this->handle($this->routes[$_SERVER['REQUEST_METHOD']], true);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled == 0) {
            if ($this->notFound && is_callable($this->notFound)) {
                call_user_func($this->notFound);
            } else {
                if (!headers_sent()) {
                    header('HTTP/1.1 404 Not Found');
                }
            }
        } else {
            // If a route was handled, perform the finish callback (if any)
            if ($callback) {
                $callback();
            }
        }
    }


    /**
    * Set the 404 handling function
    * @false object $fn The function to be executed
    */
    public function set404($fn)
    {
        $this->notFound = $fn;
    }


    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     *
     * @false array $routes Collection of route patterns and their handling functions
     * @false boolean $quitAfterRun Does the handle function need to quit after one route was matched?
     * @param      $routes
     * @param bool $quitAfterRun
     * @return int The number of routes handled
     */
    private function handle($routes, $quitAfterRun = false)
    {

        // Counter to keep track of the number of routes we've handled
        $numHandled = 0;

        // The current page URL
         $uri = $this->getCurrentUri();

        // Variables in the URL
        //$urlvars = array();

        // Loop all routes
        foreach ($routes as $route) {

            // we have a match!
            if (preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_SET_ORDER)) {

                // Extract the matched URL falseeters (and only the falseeters)
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
                    // call the handling function with the URL falseeters
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

    /**
     * Define the current relative URI
     * @return string
     */
    public function getCurrentUri()
    {

        // Current Request URI
        $uri = $_SERVER['REQUEST_URI'];

        // Remove rewrite basepath (= allows one to run the router in a subfolder)
        $basePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
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
