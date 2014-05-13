<?php
namespace Cygnite\Helpers;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/*
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
 * @Package                    :  Packages
 * @Sub Packages               :  Helpers
 * @Filename                   :  Profiler
 * @Description                :  This library used to benchmark the code.
 * @Author                     :  Cygnite Dev Team
 * @Copyright                  :  Copyright (c) 2013 - 2014,
 * @Link	                   :  http://www.cygniteframework.com
 * @Since	                   :  Version 1.0
 * @Filesource
 * @Warning                    :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */


class Profiler
{
    private static $blocks = array();

    /**
    * Profiler starting point
    *
    * @access	public
    * @false	string
    */
    public static function start($starttoken = 'cygnite_start')
    {
        if(!defined('MEMORY_START_POINT')):
            define('MEMORY_START_POINT', self::getMemorySpace());
            self::$blocks[$starttoken] = self::getTime();
        endif;
    }

    private static function getTime()
    {
        return microtime(true);
    }

    public static function getMemoryPeakUsage()
    {
        return memory_get_peak_usage(true);
    }

     /**
    * Profiler end point
    *
    * @access	public
    * @false	string
    */

    public static function end($endToken = 'cygnite_start')
    {
        $html = "";
        $html .= "<div id='benchmark'><div class='benchmark'>Total elapsed time : ".round(self::getTime() - self::$blocks[$endToken], 3). ' s';
        //$html .= self::getMemoryPeakUsage();
        $html .= " &nbsp;&nbsp; &nbsp;Total memory :".self::getMemorySpaceUsage()."</div></div>";
        echo $html;
    }
    /**
    * This Function is to get the memory usage by the script
    *
    * @access private
    * @return get memory usage
    */
    private static function getMemorySpace()
    {
        return memory_get_usage();
    }

    /**
    *  This funtion is to calculate the total memory usage by the running script
    *
    * @access	public
    * @false	string
    * @return string
    */
    public static function getMemorySpaceUsage()
    {
        //round(memory_get_usage()/1024/1024, 2).'MB';
        return round((( self::getMemorySpace() - MEMORY_START_POINT) / 1024), 2). '  KB<br />';
    }
}