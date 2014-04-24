<?php
namespace Cygnite;

use Closure;

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
 * @Package             :  Packages
 * @Sub Packages        :  Cygnite
 * @Filename            :  Security
 * @Description         :  Security package : GLOBAL variables will be accessed securely through Security package.
 *                         This package provides necessary in built validation for users data.
 * @Author              :  Sanjoy Dey
 * @Copyright           :  Copyright (c) 2013 - 2014,
 * @Link	            :  http://www.cygniteframework.com
 * @Since	            :  Version 1.0
 * @FileSource
 */
/**
 * <code>
 *  $this->instance =Security::instance(
 *          function ($instance) {
 *               return $instance;
 *          }
 *       );
 *  $this->instance->sanitize($string);
 * </code>
 * Inspired by TravianZ and Kohana security library http://kohanaphp.com/
 *
 */

class Security
{
    // Instance of the security class.
    private static $instance;

    private $magicQuotesGpc = false;

    const PREG_PROPERTIES = '/^\pL$/u';

    // Hold cleaned input
    public static $cleaned;

    public $post = array();

    public $get = array();

    public $cookie = array();

    // The following globals are standard and shouldn't really be removed
    private $_superGlobals = array('GLOBALS', '_REQUEST', '_GET', '_POST',
        '_FILES', '_COOKIE', '_SERVER', '_ENV', '_SESSION'
    );

    private $sqlReplace = array(
        '/[\']/', '/--/', '/\bdrop\b/i', '/\bdelete\b/i', '/\binsert\b/i', '/\bupdate\b/i'
    );

    /**
     * Gets the instance of the Security class.
     *
     * @param callable| Closure $callback
     * @return object Instance of Security
     */
    public static function instance(Closure $callback)
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return $callback(self::$instance);
    }


    /**
     * Constructor. Sanitizes global data GET, POST and COOKIE data.
     * Also makes sure those magic quotes and register globals
     * don't bother us. This is private because we don't want it to
     * access instance directly.
     *
     *
     * @throws \Exception
     * @return \Cygnite\Security
     */
    private function __construct()
    {
        $this->checkMagicQuoteRuntime();

        if (get_magic_quotes_gpc()) {
            // This is also deprecated. See http://php.net/magic_quotes for more information.
            $this->magicQuotesGpc = true;
        }

        //Check for register globals and prevent security issues from arising.
        if (ini_get('register_globals')) {
            if (isset($_REQUEST['GLOBALS'])) {
                //No no no..just throw exception.
                throw new \Exception('Illegal attack on global variable.');
            }

            // Get rid of REQUEST
            $_REQUEST = array();
            $this->disableGlobals();
        }

        $this->post = $this->cleanPostArray();
        $this->get = $this->cleanGetArray();
        $this->cookie = $this->cleanCookieArray();

        //Merge POST and GET to REQUEST.
        $_REQUEST = array_merge($this->get, $this->post);
    }

    /**
     * Check magic quote and disable it
     */
    private function checkMagicQuoteRuntime()
    {
        // Check for magic quotes
        if (get_magic_quotes_runtime()) {
            // Oh god! Danger. Magic quote deprecated. Sort it out.
            @set_magic_quotes_runtime(0);
        }
    }

    /**
     * disable all global variable
     */
    private function disableGlobals()
    {
        // Same effect as disabling register_globals
        foreach ($GLOBALS as $key => $value) {
            if (!in_array($key, $this->_superGlobals)) {
                global $$key;
                $$key = null;
                unset($GLOBALS[$key], $$key);
            }
        }
    }

    /**
     * @return array
     */
    private function cleanPostArray()
    {
        $this->post = $_POST;
        // Sanitize global data
        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                $_POST[$this->cleanKeys($key)] = $this->sanitize($value);
            }
        } else {
            $_POST = array();
        }

        return $_POST;

    }

    /**
     * @return array
     */
    private function cleanGetArray()
    {
        if (is_array($_GET)) {
            foreach ($_GET as $key => $value) {
                $_GET[$this->cleanKeys($key)] = $this->sanitize($value);
            }
        } else {
            $_GET = array();
        }

        return $_GET;

    }

    /**
     * @return array
     */
    private function cleanCookieArray()
    {
        if (is_array($_COOKIE)) {
            foreach ($_COOKIE as $key => $value) {
                $_COOKIE[$this->cleanKeys($key)] = $this->sanitize($value);
            }
        } else {
            $_COOKIE = array();
        }

        return $_COOKIE;

    }

    /**
     * Cross site filtering (XSS). Recursive.
     *
     * @param  string Data to be cleaned
     * @return mixed
     */
    public function xssClean($data)
    {
        // If its empty there is no point cleaning it :\
        if (empty($data)) {
            return $data;
        }

        // Recursive loop for arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->xssClean($data);
            }

            return $data;
        }

        // Fix &entity\n;
        $data = $this->fixEntity($data);

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = $this->removeJavaScriptProtocols($data);

        $data = $this->removeVbScriptProtocols($data);

        $data = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u',
            '$1=$2nomozbinding...',
            $data
        );

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = $this->fixIe($data);

        // Remove namespaces elements (we do not need them)
        $data = $this->removeNameSpaceElements($data);

        $data = preg_replace($this->sqlReplace, '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace(
                '#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l
                (?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i',
                '',
                $data
            );

        } while ($old_data !== $data);

        return htmlentities($data);
    }

    private function fixEntity($data)
    {
        $data = str_replace(array('&','<','>'), array('&','<','>'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        header('Content-Type: text/html; charset=utf-8');
        //$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        return $data;
    }

    private function removeJavaScriptProtocols($data)
    {
        return preg_replace(
            '#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
            '$1=$2nojavascript...',
            $data
        );
    }

    private function removeVbScriptProtocols($data)
    {
        return preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s
            [\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
            '$1=$2novbscript...',
            $data
        );
    }

    private function fixIe($data)
    {
        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i',
            '$1>',
            $data
        );
        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i',
            '$1>',
            $data
        );
        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i
            [\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu',
            '$1>',
            $data
        );

        return $data;
    }

    private function removeNameSpaceElements($data)
    {
        return preg_replace(
            '#</*\w+:\w[^>]*+>#i',
            '',
            $data
        );
    }

    public static function _xssClean(&$item, &$key)
    {

        $item = htmlspecialchars($item, ENT_QUOTES);
        $item = preg_replace_callback(
            '!&amp;#((?:[0-9]+)|(?:x(?:[0-9A-F]+)));?!i',
            array(__CLASS__, 'decode'),
            $item
        );
        // PERL
        $item = preg_replace(
            '!<([A-Z]\w*)
            (?:\s* (?:\w+) \s* = \s* (?(?=["\']) (["\'])(?:.*?\2)+ | (?:[^\s>]*) ) )*
            \s* (\s/)? >!ix',
            '<\1\5>',
            strip_tags(html_entity_decode($item))
        );

        self::$cleaned = $item;
    }

    public static function decode($matches)
    {
        if (!is_int($matches[1]{0})) {
            $val = '0'.$matches[1]+0;
        } else {
            $val = (int) $matches[1];
        }

        if ($val > 255) {
            return '&#'.$val.';';
        }

        if ($val >= 65 && $val <= 90  //A-Z
            || $val >= 97 && $val <= 122 // a-z
            || $val >= 48 && $val <= 57
        ) {// 0-9

            return chr($val);
        }

        return $matches[0];
    }

    public function doValidation($key, $value)
    {
        if (is_array($key)) {
            array_walk_recursive($key, array(__CLASS__,'clean'));
        } else {
            self::_xssClean($key, $value);
        }

        return (self::$cleaned !== null) ? self::$cleaned : null;
    }

    public static function clean($item, $key)
    {
        self::_xssClean($item, $key);
    }

    /**
     * Enforces W3C specifications to prevent malicious exploitation.
     *
     * @param  string Key to clean
     * @throws \Exception
     * @return string
     */
    protected function cleanKeys($data)
    {
        $pregMatches = (bool) preg_match(self::PREG_PROPERTIES, '?');
        $chars = '';
        $chars = $pregMatches ? '\pL' : 'a-zA-Z';


        if (!preg_match('#^[' . $chars . '0-9:_.-]++$#uD', $data)) {
            throw new \Exception('Illegal key characters in global data');
        }

        return $data;
    }

    /**
     * Escapes data.
     *
     * @param  mixed Data to clean
     * @return mixed
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            $new_array = array();
            foreach ($data as $key => $value) {
                $new_array[$this->cleanKeys($key)] = $this->sanitize($value);
            }

            return $new_array;
        }

        if ($this->magicQuotesGpc === true) {
            // Get rid of those pesky magic quotes!
            $data = stripslashes($data);
        }

        $data = $this->xssClean($data);

        return $data;
    }

    public function msEscapeString($data)
    {
        if (!isset($data) && empty($data)) {
            return '';
        }

        if (is_numeric($data)) {
            return $data;
        }

        $nonDisplayable = array(
            '/%0[0-8bcef]/',
            '/%1[0-9a-f]/',
            '/[\x00-\x08]/',
            '/\x0b/',
            '/\x0c/',
            '/[\x0e-\x1f]/'
        );
        foreach ($nonDisplayable as $regex) {
            $data = preg_replace($regex, '', $data);
        }
        $data = str_replace("'", "''", $data);

        return $data;
    }

    public function validate($key, $value)
    {
        return $this->doValidation($key, $value);
    }
}