<?php
namespace Cygnite\Base;

use Exception;
use Cygnite\Inflector;
use Cygnite\Application;
use Cygnite\Helpers\Helper;
use Cygnite\Helpers\Config;

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
 * @Filename           :  Dispatcher
 * @Description        :  Handle all user request and dispatch it.
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

class Dispatcher
{
    /**
    * The name of the entry page
    * @var string
    */
    private static $indexPage = 'index.php';

    private $router;
    /**
    * Define the router variable. default value set as false
    * @var bool
    */
    private static $router_enabled = false;

    private $default = array();

    private $routes = array();

    /**
     * @param $route
     */
    public function __construct($route)
    {
        $this->router = $route;
        $this->default['controller'] = lcfirst(
            Config::get('global_config', 'default_controller')
        );
        $this->default['action'] = lcfirst(
            Config::get('global_config', 'default_method')
        );

    }

    /**
     * @param      $routes
     * @param bool $quitAfterRun
     * @return mixed
     */
    public function matches($routes, $quitAfterRun = false)
    {
        //$uri = $this->router->getCurrentUri();
        $uri = str_replace('/index.php', '', rtrim($this->router->getCurrentUri()));
        //Counter to keep track of the number of routes we've handled
        $numHandled = 0;
        foreach ($routes as $route => $val) {

            // we have a match!
            if (preg_match_all('#^' . $route . '$#', $uri, $matches, PREG_SET_ORDER)) {

                    //Extract the matched URL false seters (and only the false seters)
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
                    //call_user_func_array($route['fn'], $params);
                    $routerArray['controllerPath'] = $val;
                    $routerArray['params'] = $params;
                    // yay!
                    $numHandled++;

                    // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }

                return $routerArray;
            }

        }
    }

    /**
     * Run user quest and call controller
     *
     * @return mixed
     */
    public function run()
    {
        $dispatcher = null;
        $dispatcher = $this;

        // If no argument passed or single slash call default controller
        if ($this->router->getCurrentUri() == '/' ||
            $this->router->getCurrentUri() == '/'.self::$indexPage
        ) {

            if ($this->default['controller'] != '') {

                //Static route: / (Default Home Page)
                $response = $this->router->get(
                    '/',
                    function () use ($dispatcher) {

                        return Application::instance(
                            function($app) use($dispatcher)
                            {
                                $controller = $app->getController($dispatcher->default['controller']);
                                $action = $app->getActionName($dispatcher->default['action']);

                                $instance = $app->make($controller);
                                $app->propertyInjection($instance, $controller);
                                return call_user_func_array(array($instance, $action), array());
                            }
                        );
                    }
                );
            }
        } else {

            $routeConfig = Config::get('routing_config');

            $newUrl = str_replace('/index.php', '', rtrim($this->router->getCurrentUri()));

            $exp= array_filter(explode('/', $newUrl));
            $matchedUrl = $this->matches($routeConfig);

            //Check with router configuration if matched then call defined controller
            if (!is_null($matchedUrl)) {

                $requestUri = preg_split('/[\.\ ]/', $matchedUrl['controllerPath']);

                $response  = Application::instance(
                    function($app) use($requestUri, $matchedUrl, $dispatcher)
                    {
                        $controller = $app->getController($requestUri[0]);
                        $action = $app->getActionName($requestUri[1]);

                        if (!class_exists($controller)) {
                            throw new Exception('Unhandled Exception (404 Page)');
                        }

                        $params = (array)$matchedUrl['params'];

                        $instance = $app->make($controller);
                        $app->propertyInjection($instance, $controller);

                        return call_user_func_array(array($instance, $action), $params);
                    }
                );

            } else {
                // Process user request provided in url
                $response  = Application::instance(
                    function($app) use($exp, $dispatcher)
                    {

                        $controller = $app->getController($exp[1]);
                        $instance = null;

                        $action = isset($exp[2]) ? $exp[2] : 'index';
                        $action = $app->getActionName($action);


                        if (!class_exists($controller)) {
                            throw new Exception('Unhandled Exception (404 Page)');
                        }

                        $params = array_slice($exp, 2);

                        $instance = $app->make($controller);
                        $app->propertyInjection($instance, $controller);

                        return call_user_func_array(array($instance, $action), $params);
                    }
                );
            }
        }

        $this->router->run();

        return $response;
    }

}//End of the class
