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

use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Inflector;
use Exception;

/**
 * Cygnite Dispatcher
 *
 * Handle all user request and send response to the browser.
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
    /**
     * Define the router variable. default value set as false
     *
     * @var bool
     */
    private static $router_enabled = false;
    public $default = array();
    private $router;
    private $routes = array();

    /**
     * @param \Cygnite\Foundation\Application $app
     * @internal param $route
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->router = $app['router'];
        $this->default['controller'] = lcfirst(
            Config::get('global.config', 'default_controller')
        );
        $this->default['action'] = lcfirst(
            Config::get('global.config', 'default_method')
        );

    }

    /**
     * Validate user request and send response to browser
     *
     * @throws \Exception
     * @return mixed
     */
    public function run()
    {
        // If no argument passed or single slash call default controller
        if ($this->router->getCurrentUri() == '/' ||
            $this->router->getCurrentUri() == '/' . self::$indexPage
        ) {
            if ($this->default['controller'] != '') {

                list($controller, $action) = $this->getControllerAndAction(
                    $this->default['controller'],
                    $this->default['action']
                );
                $response = $this->handleControllerDependencies($controller, $action);
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
                list($controller, $action) = $this->getControllerAndAction($requestUri[0], $requestUri[1]);

                if (!class_exists($controller)) {
                    throw new Exception('Unhandled Exception (404 Page)');
                }

                $params = (array)$matchedUrl['params'];
                $response = $this->handleControllerDependencies($controller, $action, $params);

            } else {
                // Process user request provided in url
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
                            "\\" . APPPATH . $this->app->namespace . $exp[1]
                        )
                    )
                )
                ) {
                    $controllerDir = ucfirst($exp[1]);
                    $controller = $exp[2];
                    $method = $exp[3];
                    $params = array_slice($exp, 3);
                }

                $action = isset($method) ? $method : 'index';
                list($controller, $action) = $this->getControllerAndAction($controller, $action, $controllerDir);

                if (!class_exists($controller)) {
                    throw new Exception('Unhandled Exception (404 Page)');
                }

                $response = $this->handleControllerDependencies($controller, $action, $params);
            }
        }

        return $response;
    }

    /**
     * @param        $controller
     * @param        $action
     * @param string $controllerDir
     * @return array
     */
    private function getControllerAndAction($controller, $action, $controllerDir = '')
    {
        $controller = $this->app->getController($controller, $controllerDir);
        $action = $this->app->getActionName($action);

        return array($controller, $action);
    }

    /**
     * @param       $controller
     * @param       $action
     * @param array $params
     * @return mixed
     */
    private function handleControllerDependencies($controller, $action, $params = array())
    {
        $instance = $this->app->make($controller);
        $this->app->propertyInjection($instance, $controller);
        // Trigger Events for Action
        $this->triggerActionEvent($instance, $action);
        $response = call_user_func_array(array($instance, $action), $params);
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
            call_user_func(array($instance, $type.ucfirst($action)));
        }
    }

    /**
     * @param      $routes
     * @param bool $quitAfterRun
     * @return mixed
     */
    public function matches($routes, $quitAfterRun = false)
    {
        $uri = str_replace('/index.php', '', rtrim($this->router->getCurrentUri()));
        //Counter to keep track of the number of routes we've handled
        $numHandled = 0;
        foreach ($routes as $route => $val) {

            $routePattern = $this->router->hasNamedPattern($route);
            $pattern = ($routePattern == false) ? $route : $routePattern;

            // we have a match!
            if (preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_SET_ORDER)) {

                //Extract the matched URL parameters (and only the parameters)
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

                $routerArray['controllerPath'] = $val;
                $routerArray['params'] = $params;
                $numHandled++;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }

                return $routerArray;
            }

        }
    }

}//End of the class
