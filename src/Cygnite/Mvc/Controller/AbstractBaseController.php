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

use Exception;
use Cygnite\Common\Encrypt;
use Cygnite\Helpers\Inflector;
use Cygnite\Mvc\View\ViewFactory;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Base\EventHandler\Event;
use Cygnite\Common\SessionManager\Session;
use Cygnite\Foundation\Application as App;
use Cygnite\Mvc\ControllerViewBridgeTrait;

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

    /**
     * Constructor function
     *
     * Configure parameters for View
     */
    public function __construct()
    {
        $this->configure();
    }

    //prevent clone.
    private function __clone()
    {
    }

    /**
     * Magic Method for handling errors and undefined methods.
     *
     * @param $method
     * @param $arguments
     * @return AbstractBaseController|mixed|void
     * @throws \Exception
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
     *  $response = $this->call('admin::user@index');
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

        return $this->_call(new $class, $method, $arguments);
    }

    public function getContainer()
    {
        return App::instance();
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
        foreach ($this->validProperties as $key => $property) {

            $method = 'set'.ucfirst($property);

            if ($this->property($this, $property)) {
                $this->view()->{$method}($this->{$property});
            }
        }
    }

    /**
     * @param $class
     * @param $property
     * @return bool
     */
    public function property($class, $property)
    {
        return property_exists($class, $property);
    }

    /**
     * @param       $view
     * @param array $params
     * @param bool $return
     * @return mixed
     */
    public function render($view, $params = [], $return = false)
    {
        return $this->view()->render($view, $params, $return);
    }

    /**
     * @param $view
     * @param array $params
     * @param bool $return
     * @return mixed
     */
    public function template($view, $params = [], $return = false)
    {
        return $this->view()->template($view, $params, $return);
    }

    /**
     * @return mixed
     */
    public function view()
    {
        return ViewFactory::make();
    }

    /**
     * Return the Template instance
     *
     * @return bool
     */
    public function getTemplate()
    {
        if ($this->templateEngine == false) {
            return false;
        }
        $view = $this->view();
        $view->setTwigEnvironment();

        return $view->getTemplate();
    }
}
