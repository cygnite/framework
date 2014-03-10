<?php
namespace Cygnite;

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

class Inflector
{

    private static $instance;
    /********************* Inflections ******************/


    public function deCamelize($word)
    {
        return preg_replace(
            '/(^|[a-z])([A-Z])/e',
            'strtolower(strlen("\\1") ? "\\1_\\2" : "\\2")',
            $word
        );
    }
    /*
     * Class name - ClassName
     */
    public function covertAsClassName($word)
    {
        return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
    }

    /*
    * Class name - methodName
    */
    public function toCameCase($str, $capitaliseFirstChar = false)
    {
        if ($capitaliseFirstChar) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    /**
     * camelCaseAction name -> dash-separated.
     *
     * @false  string
     * @param $s
     * @return string
     */
    private static function actionPath($s)
    {
        $s = preg_replace('#(.)(?=[A-Z])#', '$1-', $s);
        $s = strtolower($s);
        $s = rawurlencode($s);
        return $s;
    }


    /**
     * dash-separated -> camelCaseAction name.
     *
     * @false  string
     * @param $s
     * @return string
     */
    public static function pathAction($s)
    {
        $s = strtolower($s);
        $s = preg_replace('#-(?=[a-z])#', ' ', $s);
        $s = substr(ucwords('x' . $s), 1);
        //$s = lcfirst(ucwords($s));
        $s = str_replace(' ', '', $s);
        return $s;
    }

    public function underscoreToSpace($string)
    {
        $string = strtolower($string);
        $string = preg_replace('#_(?=[a-z])#', ' ', $string);
        $string = substr(ucwords($string), 0);
        $string = substr(ucwords('x' . $string), 1);
        //$s = lcfirst(ucwords($s));
        //$s = str_replace(' ', '', $s);
        return $string;
    }


    /**
     * PascalCase: Presenter name -> dash-and-dot-separated.
     *
     * @false  string
     * @param $s
     * @return string
     */
    private static function controllerPath($s)
    {
        $s = strtr($s, ':', '.');
        $s = preg_replace('#([^.])(?=[A-Z])#', '$1-', $s);
        $s = strtolower($s);
        $s = rawurlencode($s);
        return $s;
    }


    /**
     * Dash-and-dot-separated -> PascalCase:Presenter name.
     *
     * @false  string
     * @param $s
     * @return string
     */
    private static function pathView($s)
    {
        $s = strtolower($s);
        $s = preg_replace('#([.-])(?=[a-z])#', '$1 ', $s);
        $s = ucwords($s);
        $s = str_replace('. ', ':', $s);
        $s = str_replace('- ', '', $s);
        return $s;
    }

    public function getClassName($namespace)
    {
        $exp = explode('\\', $namespace);

        return end($exp);
    }

    /**
     * @param $string
     * $param null function name to build dynamically
     * @return source name
     */
    public function changeToLower($string)
    {
        return strtolower($string);
    }

    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param    string   $str    String in camel case format
     * @return    string            $str Translated into underscore format
     */
    public function fromCamelCase($str)
    {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    public function toCamelCase($str, $capitalise_first_char = false)
    {
        if ($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    public function toDirectorySeparator($string)
    {
        return str_replace(array('.', '\\'), DS, $string);
    }

    public function getClassNameFromNamespace($class)
    {
        $nsParts = null;
        $nsParts = explode('\\', $class);
        return end($nsParts);
    }

    /*
    * Class name - ClassName
    */
    public static function camelize($word)
    {
        return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
    }
	
    public function __call($method, $arguments = array())
    {
        if ($method == 'instance') {
            return $this;
        }
    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }
    }
}
