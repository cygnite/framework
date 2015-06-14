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

use Exception;
use Reflection;
use ErrorException;
use Cygnite\Helpers\Helper;
use Cygnite\Helpers\Inflector;
use Cygnite\Base\Router\Router;
use Cygnite\Foundation\Application as App;
use Cygnite\Base\Router\Controller\RouteController;


/*
 * Trait ResourceController
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

trait ResourceControllerTrait
{
    protected $resourceRoutes = ['index', 'new', 'create', 'show', 'edit', 'update', 'delete'];

    public $router;

    public $routeController;

    /**
     * Set the controller as Resource Controller
     * Cygnite Router knows how to respond to resource controller
     * request automatically
     *
     * @param $router
     * @param $name
     * @param $controller
     * @return $this
     */
    public function resourceController($router, $name, $controller)
    {
        $this->setRouter($router);

        foreach ($this->resourceRoutes as $key => $action) {
            $this->{'setResource' . ucfirst($action)}($name, $controller, $action);
        }
        return $this;
    }

    /**
     * @param $router
     */
    protected function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return \Cygnite\Base\Router\Router
     */
    protected function router()
    {
        return isset($this->router) ? $this->router : null;
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceIndex($name, $controller, $action, $options = [])
    {
        return $this->router()->match(
            strtoupper('get'),
            $name,
            function () use ($controller, $action) {
                $args = [$controller . '.' .'get'.ucfirst($action)];
                return $this->callController($args);
            }
        );
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceNew($name, $controller, $action, $options = [])
    {
        return $this->mapResource('get', $name . '/' . $action, $controller, 'get'.ucfirst($action));
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceCreate($name, $controller, $action, $options = [])
    {
        return $this->mapResource('post', $name, $controller, 'post'.ucfirst($action));
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceShow($name, $controller, $action, $options = [])
    {
        return $this->mapResource('get', $name . '/(\d+)', $controller, 'get'.ucfirst($action), true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceEdit($name, $controller, $action, $options = [])
    {
        return $this->mapResource('get', $name . '/(\d+)/edit', $controller, 'get'.ucfirst($action), true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceUpdate($name, $controller, $action, $options = [])
    {
        return $this->mapResource('put|patch', $name . '/(\d+)/', $controller, 'put'.ucfirst($action), true);
    }

    /**
     * @param       $name
     * @param       $controller
     * @param       $action
     * @param array $options
     * @return bool
     */
    protected function setResourceDelete($name, $controller, $action, $options = [])
    {
        return $this->mapResource('delete', $name . '/(\d+)/', $controller, $action, true);
    }

    /**
     * @param      $method
     * @param      $pattern
     * @param      $controller
     * @param      $action
     * @param bool $type
     * @return bool
     */
    private function mapResource($method, $pattern, $controller, $action, $type = false)
    {
        return $this->router()->match(
            strtoupper($method),
            $pattern,
            function ($router, $id) use ($controller, $action, $type) {

                $args = [$controller . '.' . $action];
                if ($type) {
                    $args = [$controller . '.' . $action, $id];// delete, update
                }

                return $this->callController($args);
            }
        );
    }
}
