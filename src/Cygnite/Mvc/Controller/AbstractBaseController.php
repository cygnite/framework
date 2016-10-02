<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Mvc\Controller;

use Cygnite\Common\UrlManager\Url;
use Cygnite\Foundation\Application;
use Cygnite\Foundation\Application as App;
use Cygnite\Helpers\Inflector;
use Cygnite\Mvc\ControllerViewBridgeTrait;
use Cygnite\Mvc\View\ViewFactory;
use Exception;

/**
 * AbstractBaseController.
 *
 * Extend the features of BaseController.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
abstract class AbstractBaseController
{
    use ControllerViewBridgeTrait;

    protected $validProperties = ['layout', 'templateEngine', 'templateExtension', 'autoReload', 'twigDebug'];

    private $class;

    protected $container;

    protected $view;

    //prevent clone.
    private function __clone()
    {
    }

    public function initialize($container)
    {
        $this->setContainer($container);
        $this->view = $container->get('view');
        $this->configure();
    }

    /**
     * Magic Method for handling errors and undefined methods.
     *
     * @param $method
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return AbstractBaseController|mixed|void
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->validFlashMessage)) {
            return $this->setFlashMessage($method, $arguments);
        }

        if (!method_exists($this, $method)) {
            throw new Exception("Undefined method [$method] called by ".get_class($this).' Controller');
        }
    }

    /**
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     *
     * @return $this
     */
    protected function redirectTo($uri = '', $type = 'location', $httpResponseCode = 302)
    {
        Url::redirectTo($uri, $type, $httpResponseCode);

        return $this;
    }

    /**
     * <code>
     * // Call the "index" method on the "user" controller
     *  $response = $this->call('admin::user@index');.
     *
     * // Call the "user/admin" controller and pass parameters
     *   $response = $this->call('modules.admin.user@profile', $arguments);
     * </code>
     */
    public function call($resource, $arguments = [])
    {
        list($name, $method) = explode('@', $resource);

        $method = $method.'Action';
        $class = array_map('ucfirst', explode('.', $name));
        $className = Inflector::classify(end($class)).'Controller';
        $namespace = str_replace(end($class), '', $class);
        $class = '\\'.APP_NS.'\\'.implode('\\', $namespace).$className;

        return $this->_call(new $class(), $method, $arguments);
    }

    /**
     * Set Application instance.
     *
     * @param $app
     *
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get application instance.
     *
     * @return mixed
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * @param $name
     */
    public function setController($name)
    {
        $this->class = $name;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return isset($this->class) ? $this->class : get_called_class();
    }

    public function configure()
    {
        //$this->view();
        foreach ($this->validProperties as $key => $property) {
            $method = 'set'.ucfirst($property);
            if ($this->property($this, $property)) {

                $this->view->{$method}($this->{$property});
            }
        }
    }

    /**
     * @param $class
     * @param $property
     *
     * @return bool
     */
    public function property($class, $property)
    {
        return property_exists($class, $property);
    }

    /**
     * @param       $view
     * @param array $params
     * @param bool  $return
     *
     * @return mixed
     */
    public function render($view, $params = [], $return = false)
    {
        return $this->view->render($view, $params, $return);
    }

    /**
     * @param $view
     * @param array $params
     * @param bool  $return
     *
     * @return mixed
     */
    public function template($view, $params = [], $return = false)
    {
        return $this->view->template($view, $params, $return);
    }

    /**
     * @return mixed
     */
    public function view()
    {
        //ViewFactory::setApplication($this->container);
        //$this->view = ViewFactory::make();
        return $this->view;
    }

    /**
     * Return the Template instance.
     *
     * @return bool
     */
    public function getTemplate()
    {
        if ($this->templateEngine == false) {
            return false;
        }
        //$view = $this->view();
        $this->view->setTwigEnvironment();

        return $this->view->getTemplate();
    }
}
