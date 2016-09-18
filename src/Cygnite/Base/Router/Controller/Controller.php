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
use Cygnite\Helpers\Inflector;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Class Controller.
 */
class Controller implements RouteControllerInterface
{
    protected $controllerRoutes = ['index', 'add', 'edit', 'show', 'delete'];

    protected $verbs = [
        'any', 'get', 'post', 'put', 'patch',
        'delete', 'head', 'options',
    ];

    private $routes = [];

    private $router;

    /**
     * @param $router
     */
    public function setRouter($router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Set the controller as Route Controller
     * Cygnite Router knows how to respond to routes controller
     * request automatically.
     *
     * @param $controller
     *
     * @return $this
     */
    public function routeController($controller)
    {
        $actions = $this->getActions();

        foreach ($actions as $key => $action) {
            $method = ucfirst(Inflector::pathAction($action));

            if (method_exists($this, 'set'.$method.'Route')) {
                $this->{'set'.$method.'Route'}(Inflector::deCamelize($controller), $action);
            }
        }

        $this->mapRoute();

        return $this;
    }

    /**
     * @param $actions
     *
     * @return array
     */
    public function setActions($actions)
    {
        $this->controllerRoutes = array_merge($this->controllerRoutes, $actions);

        return $this;
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
     *
     * @return mixed
     */
    protected function setIndexRoute($controller, $action)
    {
        $this->routes['get'] = [
            "/$controller/"              => Inflector::classify($controller).'@'.$action,
            "/$controller/$action/{:id}" => Inflector::classify($controller).'@'.$action,
            "/$controller/$action/"      => Inflector::classify($controller).'@'.$action,
        ];

        return $this;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return mixed
     */
    protected function setAddRoute($controller, $action)
    {
        $callTo = Inflector::classify($controller).'@'.$action;

        $this->routes['get'] = array_merge($this->routes['get'], [
            "/$controller/$action/" => $callTo,
        ]);

        $this->routes['post'] = [
            "/$controller/$action/" => $callTo,
        ];

        return $this;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return mixed
     */
    protected function setEditRoute($controller, $action)
    {
        $callTo = Inflector::classify($controller).'@'.$action;

        $this->routes['get'] = array_merge($this->routes['get'], [
            "/$controller/$action/{:id}/" => $callTo,
        ]);

        $this->routes['post'] = array_merge($this->routes['post'], [
            "/$controller/$action/" => $callTo,
        ]);

        return $this;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return mixed
     */
    protected function setShowRoute($controller, $action)
    {
        $this->routes['get'] = array_merge($this->routes['get'], [
            "/$controller/$action/{:id}/" => Inflector::classify($controller).'@'.$action,
        ]);

        return $this;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return mixed
     */
    protected function setDeleteRoute($controller, $action)
    {
        $this->routes['get'] = array_merge($this->routes['get'], [
            "/$controller/$action/{:id}/" => Inflector::classify($controller).'@'.$action,
        ]);

        return $this;
    }

    /**
     * @param $pattern
     * @param $func
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function mapRoute()
    {
        foreach ($this->routes['get'] as $pattern => $func) {
            $this->mapStaticRoutes($pattern, $func);
        }

        foreach ($this->routes['post'] as $pattern => $func) {
            $this->mapStaticRoutes($pattern, $func, 'post');
        }

        return $this;
    }

    /**
     * @param type $pattern
     * @param type $func
     * @param type $method
     *
     * @return \Cygnite\Base\Router\Controller\Controller
     */
    private function mapStaticRoutes($pattern, $func, $method = 'get')
    {
        if (!is_string($func)) {
            throw new \Exception("$func must be string!");
        }

        $this->router->{$method}($pattern, $func);

        return $this;
    }

    /**
     * Route to controller action using HTTP verbs
     * and defined pattern names as arguments.
     *
     * @param $controller
     */
    public function implicitController($controller)
    {
        $reflection = (new \Cygnite\Reflection())->setClass($controller);
        $methods = $reflection->getMethods('public', false, null);
        $app = $this->getContainer();

        foreach ($methods as $key => $method) {
            if ($method !== '__construct') {
                list($uri, $verb, $method, $plain) = $this->getRoutesParameters($method, $controller, $reflection);
                $args = $this->getUriArguments($plain);

                if (!in_array($verb, $this->verbs)) {
                    throw new \RuntimeException("Invalid HTTP verb ($verb) exception.");
                }

                $classParam = ['controller' => $controller, 'method' => $method, 'args' => $args];

                $this->handleRoute($app, $classParam, $verb, $uri);
            }
        }
    }

    /**
     * Route to controller action.
     *
     * @param $app
     * @param $classParam
     * @param $verb
     * @param $uri
     */
    public function handleRoute($app, $classParam, $verb, $uri)
    {
        $app->router->{$verb}($uri, function () use ($app, $classParam) {
            extract($classParam);
            $app['response'] = $app->router->handleControllerDependencies($controller, $method, $args);

            return $app['response'];
        });
    }

    /**
     * @param        $method
     * @param        $controller
     * @param        $reflection
     * @param string $replace
     *
     * @return array
     */
    public function getRoutesParameters($method, $controller, $reflection, $replace = 'Controller')
    {
        $actionName = str_replace('Action', '', $method);
        $routeArr = $this->getActionName($actionName);

        $verb = isset($routeArr[0]) && in_array($routeArr[0], $this->verbs) ? $routeArr[0] : 'get';
        /*
         | For deleteAction HTTP verb name act as method name
         */
        $action = isset($routeArr[1]) ? $actionName : $verb.ucfirst($verb);

        $prefix = str_replace($replace, '', Inflector::getClassNameFromNamespace($controller));

        $plain = $this->getPlainUri($action, Inflector::controllerPath($prefix));
        $uri = $this->addUriWildcards($plain, $reflection, $method);

        return [$uri, $verb, $method, $plain];
    }

    public function getContainer()
    {
        return $this->router->getContainer();
    }

    /**
     * Return uri arguments.
     *
     * @param $url
     *
     * @return array
     */
    public function getUriArguments($url)
    {
        $uriParam = str_replace($url, '', $this->router->getCurrentUri());

        return array_filter(explode('/', $uriParam));
    }

    /**
     * Get controller action name.
     *
     * @param $name
     *
     * @return array
     */
    public function getActionName($name)
    {
        return explode('_', Inflector::deCamelize($name));
    }

    /**
     * Extract the verb from a controller action.
     *
     * @param string $name
     *
     * @return string
     */
    public function getVerb($name)
    {
        return $this->head($this->getActionName($name));
    }

    public function head($data)
    {
        return reset($data);
    }

    public function getPlainUri($name, $prefix)
    {
        return $prefix.'/'.implode('-', array_slice(explode('_', Inflector::deCamelize($name)), 1));
    }

    /**
     * Add wildcards to the given URI.
     *
     * @param string $uri
     *
     * @return string
     */
    public function addUriWildcards($uri, $reflection, $method)
    {
        $refAction = $reflection->getReflectionClass()->getMethod($method);

        $parameter = '';
        $patterns = $this->router->getPattern();
        $arguments = new \CachingIterator(new \ArrayIterator($refAction->getParameters()));

        foreach ($arguments as $key => $param) {
            if (!$param->isOptional()) {
                if (array_key_exists('{:'.$param->getName().'}', $patterns)) {
                    $slash = ($arguments->hasNext()) ? '/' : '';
                    $parameter .= '{:'.$param->getName().'}'.$slash;
                }
            }
        }


        return $uri.'/'.$parameter;
    }
}
