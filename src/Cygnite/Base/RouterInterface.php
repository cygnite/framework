<?php
namespace Cygnite\Base;

use Exception;
use Reflection;
use ErrorException;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Helpers\Helper;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
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
 * @Package            :  Packages
 * @Sub Packages       :  Base
 * @Filename           :  RouterInterface
 * @Description        :
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link               :  http://www.cygniteframework.com
 * @Since              :  Version 1.0
 *
 *
 */
interface RouterInterface
{
    public function before($methods, $pattern, $fn);

    public function get($pattern, $fn);

    public function post($pattern, $fn);

    public function delete($pattern, $fn);

    public function put($pattern, $fn);

    public function options($pattern, $fn);

    public function set404($fn);

    public function run($callback = null);
}