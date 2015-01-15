<?php
namespace Cygnite\Exception;

use Closure;
use Exception;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
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
 * @Package                     :  Packages
 * @Sub Packages          :
 * @Filename                  :  Template
 * @Description             :  This file is used to Define all necessary configurations for template engine
 * @Author                     :  Sanjoy Dey
 * @Copyright              :  Copyright (c) 2013 - 2014,
 * @Link                           :  http://www.cygniteframework.com
 * @Since                     :  Version 1.0
 * @FileSource
 *
 */

interface ExceptionInterface 
{

    /**
     * Display the given exception to the user.
     */
    public function run();

    public static function register(Closure $callback);

}
