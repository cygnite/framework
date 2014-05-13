<?php
namespace Cygnite\Helpers;

use Cygnite\Helpers\Html;
use InvalidArgumentException;

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
 * @Sub Packages               :  Helper
 * @Filename                   :  Assets
 * @Description                :  This helper is used to load all assets of Html page. Not implemented in current version. May be available on next version.
 * @Author                     :  Cygnite Dev Team
 * @Copyright                  :  Copyright (c) 2013 - 2014,
 * @Link	                   :  http://www.cygniteframework.com
 * @Since	                   :  Version 1.0
 * @Filesource
 * @Warning                    :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Assets
{
    /**
     * Generate a link to a stylesheet file.
     *
     * <code>
     *         // Generate a link to a stylesheet file
     *            echo Assets::addStyle('css/cygnite.css');
     * </code>
     *
     * @false  string  $href
     * @false  array   $type
     * @param        $href
     * @param string $media
     * @param string $title
     * @throws InvalidArgumentException
     * @return string
     */
    public static function addStyle($href, $media = '', $title = '')
    {
        if (is_null($href)) {
             throw new InvalidArgumentException("Cannot pass null argument to ".__METHOD__);
        }

        $media = (is_null($media)) ? 'media=all' : $media;
        $title = (!is_null($title)) ? "title='$title'"  : '';

        return
        '<link rel="stylesheet"
        type="text/css" '.$media.' '.$title.' href="'.Url::getBase().$href.'" rel="stylesheet">'.PHP_EOL;
    }

    /**
     * Generate a link to a JavaScript file.
     *
     * <code>
     *  // Generate a link to a JavaScript file
     *    echo Assets::addScript('js/jquery.js');
     *
     * // Generate a link to a JavaScript file and add some attributes
     *   echo Assets::addScript('js/jquery.js', array('required'));
     * </code>
     *
     * @false  string  $url
     * @false  array   $attributes
     * @param       $url
     * @param array $attributes
     * @return string
     */
    public static function addScript($url, $attributes = array())
    {
        return
        '<script type="text/javascript"
        src="'.Url::getBase().$url.'"'.self::addAttributes($attributes).'></script>'.PHP_EOL;
    }



    public static function ajax($url, $data, $method, $callback, $callType = '')
    {
        $ajaxScript = '';
        $ajaxScript = '<script type="text/javascript">';
        $ajaxScript = '
                                $.ajax();
                                ';

        $ajax_script = '</script>';
    }

    /**
     * Generate anchor link
     *
     * @false  string     $url
     * @false  string     $name
     * @false  array   $attributes
     * @param       $url
     * @param null  $name
     * @param array $attributes
     * @return string
     */
    public static function addLink($url, $name = null, $attributes = array())
    {
         $name =  (is_null($name)) ? $url :  $name;
         return
         '<a href="'.Url::getBase().Html::entities($url).'"
         '.self::addAttributes($attributes).'>'.Html::entities($name).'</a>'.PHP_EOL;
    }

    /**
     * Form Html attributes from array.
     *
     * @false  array   $attributes
     * @param array $attributes
     * @param array $html
     * @return string
     */
    public static function addAttributes($attributes = array(), $html = array())
    {
        foreach ($attributes as $key => $value) {
            if (!is_null($value)) {
                $html[] = $key.'="'.Html::entities($value).'"';
            }
        }

        return (count($html) > 0) ? ' '.implode(' ', $html) : '';
    }
}
