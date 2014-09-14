<?php
namespace Cygnite\Helpers;

use Cygnite\Proxy\StaticResolver;

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
 * @Filename                 :  Inflector
 * @Description              :  This class is used to make class name, method name and others.
 * @Author                   :  Cygnite Dev Team
 * @Copyright                :  Copyright (c) 2013 - 2014,
 * @Link	                 :  http://www.cygniteframework.com
 * @Since	                 :  Version 1.0
 * @Filesource
 * @Warning                  :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Inflector extends StaticResolver
{

    private static $instance;
    /********************* Inflections ******************/

    /**
     *
     * class_name - ClassName
     * Convert underscore or - separated string to class name
     *
     * foo_bar -> FooBar
     * foo-bar -> FooBar
     *
     * @param $word string
     * @return mixed
     */
    public function classify($word)
    {
        //return preg_replace('/(^|_|-)([a-z])/e', 'strtoupper("\\2")', $word);
        $s = strtolower(trim($word));
        $s = preg_replace('#([.-])(?=[a-z])#', '$1 ', $s);
        $s = preg_replace('#([._])(?=[a-z])#', '$1 ', $s);
        $s = ucwords($s);
        $s = str_replace('. ', ':', $s);
        return $s = str_replace(array('_ ', '- '), '', $s);
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

    /**
     * @param $string
     * @return string
     */
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
     * PascalCase: name -> dash-and-dot-separated.
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
     * Dash-and-dot-separated -> PascalCase:name.
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
    public function tabilize($str)
    {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitaliseFirstChar   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    public function toCamelCase($str, $capitaliseFirstChar = false)
    {
        if ($capitaliseFirstChar) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    /**
     * @param        $word
     * @param string $splitter
     * @return mixed
     */
    public function deCamelize($word, $splitter = '_')
    {
        /*return preg_replace(
            '/(^|[a-z])([A-Z])/e',
            'strtolower(strlen("\\1") ? "\\1_\\2" : "\\2")',
            $word
        );*/
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1'.$splitter.'$2', trim($word)));
    }

    /**
     * @param $string
     * @return mixed
     */
    public function toDirectorySeparator($string)
    {
        return str_replace(array('.', '\\'), DS, $string);
    }

    /**
     * @param $class
     * @return mixed
     */
    public function getClassNameFromNamespace($class)
    {
        $nsParts = null;
        $nsParts = explode('\\', $class);
        return end($nsParts);
    }

    /**
     * Covert dash-dot to namespace
     *
     * @param $key
     * @return string
     */
    public function toNamespace($key)
    {
        $class = null;
        $class = explode('.', $key);
        $class = array_map('ucfirst', $class);
        $class = array_map('self::classify', $class);
        $class = '\\'.implode('\\', $class);

        return $class;
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

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }
    }

    /**
     * @param $word
     * @return mixed|string
     */
    public function pluralize($word)
    {
        $result = strval($word);

        if (in_array(strtolower($result), $this->uncountableWords())) {
            return $result;
        } else {
            foreach($this->pluralRules() as $rule => $replacement) {
                if (preg_match($rule, $result)) {
                    $result = preg_replace($rule, $replacement, $result);
                    break;
                }
            }

            return $result;
        }
    }

    /**
     * @param $word
     * @return mixed|string
     */
    public function singularize($word)
    {
        $result = strval($word);

        if (in_array(strtolower($result), $this->uncountableWords())) {
            return $result;
        } else {
            foreach($this->singularRules() as $rule => $replacement) {
                if (preg_match($rule, $result)) {
                    $result = preg_replace($rule, $replacement, $result);
                    break;
                }
            }

            return $result;
        }
    }


    public function uncountableWords()
    {
        #:doc
        return array( 'equipment', 'information', 'rice', 'money', 'species', 'series', 'fish' );
    }

    public function pluralRules()
    {
        #:doc:
        return array(
            '/^(ox)$/'                => '\1\2en',     # ox
            '/([m|l])ouse$/'          => '\1ice',      # mouse, louse
            '/(matr|vert|ind)ix|ex$/' => '\1ices',     # matrix, vertex, index
            '/(x|ch|ss|sh)$/'         => '\1es',       # search, switch, fix, box, process, address
            #'/([^aeiouy]|qu)ies$/'    => '\1y', -- seems to be a bug(?)
            '/([^aeiouy]|qu)y$/'      => '\1ies',      # query, ability, agency
            '/(hive)$/'               => '\1s',        # archive, hive
            '/(?:([^f])fe|([lr])f)$/' => '\1\2ves',    # half, safe, wife
            '/sis$/'                  => 'ses',        # basis, diagnosis
            '/([ti])um$/'             => '\1a',        # datum, medium
            '/(p)erson$/'             => '\1eople',    # person, salesperson
            '/(m)an$/'                => '\1en',       # man, woman, spokesman
            '/(c)hild$/'              => '\1hildren',  # child
            '/(buffal|tomat)o$/'      => '\1\2oes',    # buffalo, tomato
            '/(bu)s$/'                => '\1\2ses',    # bus
            '/(alias|status)/'        => '\1es',       # alias
            '/(octop|vir)us$/'        => '\1i',        # octopus, virus - virus has no defined plural (according to Latin/dictionary.com), but viri is better than viruses/viruss
            '/(ax|cri|test)is$/'      => '\1es',       # axis, crisis
            '/s$/'                    => 's',          # no change (compatibility)
            '/$/'                     => 's'
        );
    }

    public function singularRules()
    {
        #:doc:
        return array(
            '/(matr)ices$/'         =>'\1ix',
            '/(vert|ind)ices$/'     => '\1ex',
            '/^(ox)en/'             => '\1',
            '/(alias)es$/'          => '\1',
            '/([octop|vir])i$/'     => '\1us',
            '/(cris|ax|test)es$/'   => '\1is',
            '/(shoe)s$/'            => '\1',
            '/(o)es$/'              => '\1',
            '/(bus)es$/'            => '\1',
            '/([m|l])ice$/'         => '\1ouse',
            '/(x|ch|ss|sh)es$/'     => '\1',
            '/(m)ovies$/'           => '\1\2ovie',
            '/(s)eries$/'           => '\1\2eries',
            '/([^aeiouy]|qu)ies$/'  => '\1y',
            '/([lr])ves$/'          => '\1f',
            '/(tive)s$/'            => '\1',
            '/(hive)s$/'            => '\1',
            '/([^f])ves$/'          => '\1fe',
            '/(^analy)ses$/'        => '\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
            '/([ti])a$/'            => '\1um',
            '/(p)eople$/'           => '\1\2erson',
            '/(m)en$/'              => '\1an',
            '/(s)tatuses$/'         => '\1\2tatus',
            '/(c)hildren$/'         => '\1\2hild',
            '/(n)ews$/'             => '\1\2ews',
            '/s$/'                  => ''
        );
    }
}
