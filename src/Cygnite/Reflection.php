<?php
namespace Cygnite;

use Cygnite\Base\Router;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/*
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
 * @Package                  :  Packages
 * @Sub Packages             :
 * @Filename                 :  Inflectors
 * @Description              :  This library will be available on next version
 * @Author                   :  Cygnite Dev Team
 * @Copyright                :  Copyright (c) 2013 - 2014,
 * @Link	                 :  http://www.cygniteframework.com
 * @Since	                 :  Version 1.0
 * @Filesource
 * @Warning                  :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Reflection
{

    public static function getInstance($class= null)
    {
        $reflector = null;
        echo get_called_class();
        if (class_exists($class)) {
            $reflector = new \ReflectionClass('\\'.$class);

            return new $reflector->name;
        }
    }
}
