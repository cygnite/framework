<?php
namespace Cygnite\Libraries\Cache;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3  or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *    http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 *@Package                :  Cygnite Framework abstract class for caching mechanism.
 *@Filename               :  StorageInterface.php
 *@Description            :  This file is required to implement  Cygnite caching system.
 *@Author                 :  Sanjoy Dey
 *@Copyright              :  Copyright (c) 2013 - 2014,
 *@Link	                  :  http://www.cygniteframework.com
 *@Since	              :  Version 1.0
 *@FileSource
 *@warning                :  Any changes in this library can cause abnormal behaviour of the framework
 *
 */
abstract class StorageInterface
{
    /* Abstract store method of caching*/
    abstract protected function save($key, $value);

    /* Abstract the cache retrieving function */
    abstract public function fetch($key);

    /* Abstract the cache destroy function */
    abstract public function destroy($key);
}
