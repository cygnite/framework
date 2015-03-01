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
use Cygnite\Base\Event;
use Cygnite\Common\Encrypt;
use Cygnite\Common\SessionManager\Session;
use Cygnite\Foundation\Application as App;
use Cygnite\Helpers\Inflector;
use Cygnite\Mvc\View\CView;
use Cygnite\Mvc\View\Template;

/**
 * AbstractBaseController.
 *
 * Extend the features of BaseController.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
abstract class AbstractBaseController extends CView
{
    private $validFlashMessage = array('setFlash', 'hasFlash', 'getFlash', 'hasError');

    private $class;

    /**
     * Constructor function
     *
     * @access    public
     * @return \Cygnite\Mvc\Controller\AbstractBaseController class object
     */
    public function __construct()
    {
        parent::__construct(new Template);
    }

    //prevent clone.
    private function __clone()
    {

    }

    protected function getContainer()
    {
        return App::instance();
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
     * @param $method
     * @param $arguments
     * @return AbstractBaseController|mixed
     */
    private function setFlashMessage($method, $arguments)
    {
        $flashSession = $this->get('cygnite.common.session-manager.flash.flash-message');

        return ($method == 'setFlash') ? $this : $this->_call($flashSession, $method, $arguments);
    }

    /**
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     * @return $this
     */
    protected function redirectTo($uri = '', $type = 'location', $httpResponseCode = 302)
    {
        $url = $this->get('cygnite.common.url-manager.url');
        $url->redirectTo($uri, $type, $httpResponseCode);

        return $this;
    }

    /**
     * @param $class
     * @return object @instance instance of your class
     */
    protected function get($class)
    {
        $container = $this->getContainer();
        return $container->resolve($class);
    }

    protected function _call($instance, $method, $arguments = array())
    {
        return call_user_func_array(array($instance, $method), $arguments);
    }

    /**
    <code>
     * // Call the "index" method on the "user" controller
     *  $response = $this->call('admin::user@index');
     *
     * // Call the "user/admin" controller and pass parameters
     *   $response = $this->call('modules.admin.user@profile', $arguments);
     * </code>
     */
    public function call($resource, $arguments = array())
    {
        list($name, $method) = explode('@', $resource);

        $method = $method.'Action';
        $class = array_map('ucfirst', explode('.', $name));
        $className = Inflector::instance()->classify(end($class)).'Controller';
        $namespace = str_replace(end($class), '', $class);
        $class = '\\'.ucfirst(APPPATH).'\\'.implode('\\', $namespace).$className;

        return $this->_call(new $class, $method, $arguments);
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
}
