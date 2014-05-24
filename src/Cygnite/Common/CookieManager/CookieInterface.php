<?php
namespace Cygnite\Common\CookieManager;

/**
 *   Cygnite PHP Framework
 *
 *   An open source application development framework for PHP 5.3x or newer
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
 * @Sub Packages              :
 * @Filename                  :  Cookie Interface
 * @Description               :  This library will be available on next version
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @Filesource
 * @Warning                   :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */


interface CookieInterface
{
    /**
     * @param $cookie
     * @return mixed
     */
    public function get($cookie);

    /**
     * @return mixed
     */
    public function save();

    /**
     * @param $cookie
     * @return mixed
     */
    public function destroy($cookie);

    /**
     * @param $cookie
     * @return mixed
     */
    public function has($cookie);
}