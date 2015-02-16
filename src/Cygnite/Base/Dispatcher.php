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
use Cygnite\Helpers\Inflector;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Helper;
use Cygnite\Helpers\Config;

/**
 * Cygnite Dispatcher
 *
 * Handle all user request and send response to the user request.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Dispatcher
{
    /**
     * The name of the entry page
     *
     * @var string
     */
    private static $indexPage = 'index.php';

    private $router;
    /**
     * Define the router variable. default value set as false
     *
     * @var bool
     */
    private static $router_enabled = false;

    public $default = array();

    private $routes = array();

    /**
     * @param $route
     */
    public function __construct($route)
    {
        $this->router = $route;
        $this->default['controller'] = lcfirst(
            Config::get('global.config', 'default_controller')
        );
        $this->default['action'] = lcfirst(
            Config::get('global.config', 'default_method')
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
            $this->router->getCurrentUri() == '/' . self::$indexPage
        ) {

            if ($this->default['controller'] != '') {

                //Static route: / (Default Home Page)
                $response = $this->router->get(
                    '/',
                    function () use ($dispatcher) {

                        return Application::instance(
                            function ($app) use ($dispatcher) {
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

            $routeConfig = Config::get('config.router');

            $newUrl = str_replace('/index.php', '', rtrim($this->router->getCurrentUri()));

            $exp = array_filter(explode('/', $newUrl));
            $matchedUrl = $this->matches($routeConfig);

            //Check with router configuration if matched then call defined controller
            if (!is_null($matchedUrl)) {

                $requestUri = preg_split('/[\.\ ]/', $matchedUrl['controllerPath']);

                // We are matching with static routing if match then dispatch it
                $response = Application::instance(
                    function ($app) use ($requestUri, $matchedUrl, $dispatcher) {
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
                $response = Application::instance(
                    function ($app) use ($exp, $dispatcher) {
                        $controller = $method = $param = $instance = null;
                        $controller = $exp[1];

                        if (isset($exp[2])) {
                            $method = $exp[2];
                        }

                        $params = array_slice($exp, 2);
                        $controllerDir = '';

                        if (is_dir(
                            CYGNITE_BASE . str_replace(
                                '\\',
                                DS,
                                strtolower(
                                    "\\" . APPPATH . $app->namespace . $exp[1]
                                )
                            )
                        )
                        ) {

                            $controllerDir = ucfirst($exp[1]);
                            $controller = $exp[2];
                            $method = $exp[3];
                            $params = array_slice($exp, 3);
                        }

                        $controller = $app->getController($controller, $controllerDir);

                        $action = isset($method) ? $method : 'index';
                        $action = $app->getActionName($action);

                        if (!class_exists($controller)) {
                            throw new Exception('Unhandled Exception (404 Page)');
                        }

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
