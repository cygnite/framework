<?php
namespace Cygnite\Libraries\Cache\Handler;

//use Cygnite\Singleton;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3  or newer.
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
 * @Package               :  Packages
 * @Sub Packages          :  Library
 * @Filename              :  CF_Cache
 * @Description           :  This factory class is used to load memory driver libraries based on users request
 * @Author                :  Cygnite Dev Team
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	              :  http://www.cygniteframework.com
 * @Since	              :  Version 1.0
 * @FileSource
 * @Warning               :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Cache
{
    // set static variable storage directory  path
    private static $directory = 'storage';

    // set variable file name null by default.
    private $fileName;

    //set variable driver class null by default
    private $driverClass;

    /*
    * factory pattern to include the driver library and return object of the library
    * @access public
    * @false string $type
    * @return object
    */
    public function build($type)
    {
        if (is_null($this->fileName)) {
            $this->fileName = $type;
        }

        $path = $this->getPath();
        if (is_readable($path)) {

            if (class_exists($this->getPath)) {
                return new $this->getPath();
            } else {
                throw new \Exception("Class $this->getPath not found");
            }
        } else {
            throw new \Exception("Directory not readable $path");
        }
    }

    /*
    * This function is used to get the directory path path on request
    * @access private
    * @return string or boolean
    */
    private function getPath()
    {
        $this->getPath = $this->fileName.'_Driver';
        
        return (self::$directory != "" && !is_null($this->fileName))
                ? str_replace('handler', self::$directory.'\\', dirname(__FILE__)).$this->fileName.'_Driver'.EXT
               : null;
    }
}
