<?php
namespace Cygnite\Base;

use Cygnite\Helpers\Helper;
use Cygnite\Helpers\Config;
use Cygnite\Exceptions;
use Cygnite\Inflector;

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

                    // call the handling function with the URL falseeters
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

    public function run()
    {
        if ($this->router->getCurrentUri() == '/' ||
            $this->router->getCurrentUri() == '/'.self::$indexPage
        ) {

            if ($this->default['controller'] != '') {

                $defaultController = $defaultAction = null;

                $defaultController =
                ucfirst(APPPATH).'\\Controllers\\'.Inflector::instance()->covertAsClassName($this->default['controller']).'Controller';
                $defaultAction = Inflector::instance()->toCameCase($this->default['action']).'Action';

                $callArray = array(
                                  'controller' => $defaultController,
                                  'action' => $defaultAction,
                                  'params' => array()
                );

                //Static route: / (Default Home Page)
                $response = $this->router->get(
                    '/',
                    function () use ($callArray) {
                        $homePageObject = new $callArray['controller'];
                        return $homePageObject->$callArray['action']();
                    }
                );
            }
        } else {

            $routeConfig = Config::get('routing_config');

            $newUrl = str_replace('/index.php', '', rtrim($this->router->getCurrentUri()));

            $exp= array_filter(explode('/', $newUrl));
            $matchedUrl = $this->matches($routeConfig);

            // Custom 404 Handler
            /*     $router->set404(function() {
                    header('HTTP/1.1 404 Not Found');
                    echo '404, route not found!';
            }); */

            if (!is_null($matchedUrl)) {

                $requestUri = preg_split('/[\.\ ]/', $matchedUrl['controllerPath']);
                $controller =
                ucfirst(APPPATH).'\\Controllers\\'.Inflector::instance()->covertAsClassName($requestUri[0]).'Controller';
                $action = Inflector::instance()->toCameCase($requestUri[1]).'Action';

                $response =
                    call_user_func_array(
                        array(new $controller, $action),
                        (array)$matchedUrl['params']
                    );

            } else {

                $controller = ucfirst(APPPATH).'\\Controllers\\'.Inflector::instance()->covertAsClassName($exp[1]).'Controller';

                $instance = null;
                $action = Inflector::instance()->toCameCase((!isset($exp[2])) ? $this->default['action'] : $exp[2]).'Action';

		        try {
                   $instance = new $controller();
                } catch (Exception $ex) {
                   throw new \Exception('Unhandled Exception (404 Page)');
                }

                /*
                if (!method_exists($instance, $action)) {
                    throw new \Exception("Requested action $action not found !");
                }
                */

                $params = array_slice($exp, 2);
                $response =
                    call_user_func_array(
                        array($instance, $action),
                        (array)$params
                    );
            }
        }
        $this->router->run();
 	
        return $response;
    }

}//End of the class
