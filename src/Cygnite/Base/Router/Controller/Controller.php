<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Base\Router\Controller;

use Cygnite\Helpers\Inflector;
use Cygnite\Base\Router\Router;
use Cygnite\Foundation\Application as App;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Class Controller
 *
 * @package Cygnite\Base\Router\Controller
 */
class Controller implements RouteControllerInterface
{
    protected $controllerRoutes = ['index', 'add', 'edit', 'show', 'delete'];

    /**
     * Set the controller as Route Controller
     * Cygnite Router knows how to respond to routes controller
     * request automatically
     * @param $controller
     * @return $this
     */
    public function routeController($controller)
    {
        $actions = $this->getActions();

        foreach ($actions as $key => $action) {

            $method = ucfirst(Inflector::pathAction($action));

            if (method_exists($this, 'set'.$method.'Route')) {
                $this->{'set'.$method.'Route'}(lcfirst($controller), $action);
            }
        }

        return $this;
    }

    /**
     * @param $actions
     * @return array
     */
    public function setActions($actions)
    {
        $this->controllerRoutes = array_merge($this->controllerRoutes, $actions);
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return isset($this->controllerRoutes) ? $this->controllerRoutes : [];
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setIndexRoute($controller, $action)
    {
         $this->mapRoute("/$controller/", Inflector::classify($controller).'@'.$action);
         return $this->mapRoute("/$controller/$action/", Inflector::classify($controller).'@'.$action);
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setAddRoute($controller, $action)
    {
        return $this->mapRoute("/$controller/$action/", Inflector::classify($controller).'@'.$action);
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setEditRoute($controller, $action)
    {
        return $this->mapRoute("/$controller/$action/{:id}/", Inflector::classify($controller).'@'.$action);
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setShowRoute($controller, $action)
    {
        return $this->mapRoute("/$controller/$action/{:id}/", Inflector::classify($controller).'@'.$action);
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setDeleteRoute($controller, $action)
    {
        return $this->mapRoute("/$controller/$action/{:id}/", Inflector::classify($controller).'@'.$action);
    }

    /**
     * @param $pattern
     * @param $func
     * @return mixed
     * @throws \Exception
     */
    protected function mapRoute($pattern, $func)
    {
        if (!is_string($func)) {
            throw new \Exception("$func must be string!");
        }

        return $this->mapStaticRoutes($pattern, $func);
    }

    /**
     * @param $func
     * @param $pattern
     * @throws \Exception
     * @return mixed
     */
    protected function mapStaticRoutes($pattern, $func)
    {
        $app = null;
        $app = App::instance();
        return $app['router']->get($pattern, $func);
    }
}
