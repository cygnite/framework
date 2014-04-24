<?php
namespace Cygnite;

use Exception;
use Cygnite\Application;
use Cygnite\Template;

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
 * @Package                   :  Packages
 * @SubPackages               :  Cygnite
 * @Filename                  :  Base Controller
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
    public $app;
    /**
     * Constructor function
     *
     * @access    public
     * @return \Cygnite\AbstractBaseController class object
     */
    public function __construct()
    {
        parent::__construct(new Template);
        $this->app = Application::instance();
    }

    //prevent clone.
    private function __clone()
    {

    }

    /**
     * Magic Method for handling errors.
     *
     */
    public function __call($method, $arguments)
    {
        throw new Exception("Undefined method [$method] called by ".get_class($this).' Controller');
    }

    /**
     * @param $key
     * @return @instance instance of your class
     */
    protected function get($key)
    {
        $class = null;
        $class = explode('.', $key);
        $class = array_map('ucfirst', $class);
        $class = implode('\\', $class);
        return $this->app->make('\\'.$class);
    }
 }
