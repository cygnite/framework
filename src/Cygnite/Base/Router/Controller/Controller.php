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
        foreach ($this->controllerRoutes as $key => $action) {
            if (method_exists($this, 'set'.ucfirst($action).'Route')) {
                $this->{'set'.ucfirst($action).'Route'}(lcfirst($controller), $action);
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
        array_merge($this->controllerRoutes, $actions);
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
        return $this->mapRoutes(ucfirst($controller).'.'.$action, "/$controller/");
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setAddRoute($controller, $action)
    {
        return $this->mapRoutes(ucfirst($controller).'.'.$action, "/$controller/$action/");
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setEditRoute($controller, $action)
    {
        return $this->mapRoutes(ucfirst($controller).'.'.$action, "/$controller/$action/{:id}");
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setShowRoute($controller, $action)
    {
        return $this->mapRoutes(ucfirst($controller).'.'.$action, "/$controller/$action/{:id}");
    }

    /**
     * @param $controller
     * @param $action
     * @return mixed
     */
    protected function setDeleteRoute($controller, $action)
    {
        return $this->mapRoutes(ucfirst($controller).'.'.$action, "/$controller/$action/{:id}");
    }

    /**
     * @param $func
     * @param $pattern
     * @return mixed
     */
    protected function mapRoutes($func, $pattern)
    {
        $app = null;
        $app = App::instance();
        return $app['router']->get($pattern, $func);
    }
}
