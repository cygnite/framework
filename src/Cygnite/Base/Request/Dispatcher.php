<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base\Request;

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
    public $default = [];
    private $router;
    private $routes = [];

    /**
     * @param \Cygnite\Foundation\Application $app
     * @internal param $route
     */
    public function __construct(Application $app)
    {
        $this->router = $app['router'];

        $this->default['controller'] = lcfirst(
            Config::get('global.config', 'default.controller')
        );
        $this->default['action'] = lcfirst(
            Config::get('global.config', 'default.method')
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

                $this->router->getRouteControllerInstance();

                list($controller, $action) = $this->router->getControllerAndAction(
                    $this->default['controller'],
                    $this->default['action']
                );

                return $this->router->handleControllerDependencies($controller, $action);
            }
        }

        $routes = $this->getRoutes();
        try {
            return $routes();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return callable
     */
    public function getRoutes()
    {
        return function () {
            require APPPATH.DS.'Routing'.DS.'Routes'.EXT;
        };
    }

}
