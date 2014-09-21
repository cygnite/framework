<?php
namespace Cygnite\Mvc\Controller;

use Cygnite\Common\Encrypt;
use Cygnite\Common\SessionManager\Session;
use Cygnite\Common\SessionManager\Flash\FlashMessage;
use Cygnite\DependencyInjection\Container;
use Cygnite\Helpers\Inflector;
use Exception;
use Cygnite\Foundation\Application;
use Cygnite\Base\Event;
use Cygnite\Mvc\View\CView;
use Cygnite\Mvc\View\Template;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                   :  Cygnite
 * @SubPackages               :  Mvc
 * @Filename                  :  AbstractBaseController
 * @Description               :  This is the base controller of your application.
 *                               Controllers extends all base functionality of BaseController class.
 * @Author                    :  Cygnite Dev Team
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @FileSource
 *
 */

abstract class AbstractBaseController extends CView
{
    private $validFlashMessage = array('setFlash', 'hasFlash', 'getFlash', 'hasError');

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

    public function getContainer()
    {
        return new Container();
    }

    /**
     * Magic Method for handling errors.
     *
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->validFlashMessage)) {
            $flashSession = $this->get('cygnite.common.session-manager.flash.flash-message');

            $return = call_user_func_array(array($flashSession, $method), $arguments);

            return ($method == 'setFlash') ? $this : $return;
        }

        throw new Exception("Undefined method [$method] called by ".get_class($this).' Controller');
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
        //$expression = explode('@', $resource);
        list($name, $method) = explode('@', $resource);

        $method = $method.'Action';
        $class = array_map('ucfirst', explode('.', $name));
        $className = Inflector::instance()->classify(end($class)).'Controller';
        $namespace = str_replace(end($class), '', $class);
        $class = '\\'.ucfirst(APPPATH).'\\'.implode('\\', $namespace).$className;

        return call_user_func_array(array(new $class, $method), $arguments);
    }
}
