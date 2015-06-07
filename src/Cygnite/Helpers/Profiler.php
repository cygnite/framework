<?php
namespace Cygnite\Helpers;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class Profiler - Tiny class to benchmark code
 * @package Cygnite\Helpers
 */
class Profiler
{
    private static $blocks = [];

    /**
    * Profiler starting point
    *
    * @access   public
    * @false    string
    */
    public static function start($startToken = 'cygnite_start')
    {
        if(!defined('MEMORY_START_POINT')):
            define('MEMORY_START_POINT', self::getMemorySpace());
            self::$blocks[$startToken] = self::getTime();
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
    * @access   public
    * @false    string
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
    *  This function is to calculate the total memory usage by the running script
    *
    * @access   public
    * @false    string
    * @return string
    */
    public static function getMemorySpaceUsage()
    {
        //round(memory_get_usage()/1024/1024, 2).'MB';
        return round((( self::getMemorySpace() - MEMORY_START_POINT) / 1024), 2). '  KB<br />';
    }
}