<?php
namespace Cygnite;

use Cygnite\Application;

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
 * @Description               :  This is the base loader of controller.
 *                               Controllers extends all base functionality from this BaseController.
 * @Author                    :  Cygnite Dev Team
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @FileSource
 *
 */

abstract class AbstractBaseController extends CView
{

    public static $instance;

    /**
     * Constructor function
     *
     * @access    public
     * @return \Cygnite\AbstractBaseController class object
     */
    public function __construct()
    {
        parent::__construct();
    }

    //prevent clone.
    public function __clone()
    {

    }

    /**
     * Magic Method for handling errors.
     *
     */
    public function __call($method, $arguments)
    {
        throw new \Exception("Undefined method [$method] called by ".get_class($this).' Controller');


    }

    protected function get($key)
    {
        //return new {$key};
    }

    public function getInstance($class)
    {
        //return Application::load()->$class;
    }
}