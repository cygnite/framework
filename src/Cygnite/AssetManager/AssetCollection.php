<?php
namespace Cygnite\AssetManager;

use Closure;
use Cygnite\Common\UrlManager\Url;

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
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                    :  Packages
 * @Sub                        Packages               :
 * @Filename                   :  AssetCollection
 * @Description                :  Used to get the Asset object and mange all assets
 *
 * @Author                     :  Sanjoy Dey
 * @Copyright                  :  Copyright (c) 2013 - 2014,
 * @Link                       :  http://www.cygniteframework.com
 * @Since                      :  Version 1.0
 * @FileSource
 *
 *
 */

class AssetCollection
{
    public static function make(Closure $callback)
    {
        return $callback(new Asset());
    }
}
