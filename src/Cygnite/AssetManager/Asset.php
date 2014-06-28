<?php
namespace Cygnite\AssetManager;

use Cygnite\Proxy\StaticResolver;
use Cygnite\AssetManager\Html;
use Cygnite\Common\UrlManager\Url;
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

/*
$asset = AssetCollection::make(function($asset)
{
    $asset->add('style', array('path' => 'css.cygnite.css', 'media' => '', 'title' => ''))
          ->add('style', array('path' => 'css.*', 'media' => '', 'title' => ''))
          ->add('script', array('path' => 'js.*', 'attributes' => ''))
          ->add('link', array('path' => '', 'name' => '', 'attributes' => array()));

    return $asset;
});
$asset->dump('style');
$asset->dump('script');
$asset->dump('link');
*/




class Asset extends StaticResolver implements \ArrayAccess
{
    protected $assets = array();

    private static $stylesheet = '<link rel="stylesheet" type="text/css"';

    private static $script = '<script type="text/javascript"';

    public function add($type, $arguments = array())
    {
        switch ($type) {
            case 'style':
                call_user_func_array(array($this, $type), $arguments);
                break;
            case 'script':
                call_user_func_array(array($this, $type), $arguments);
                break;
            case 'link':
                call_user_func_array(array($this, $type), $arguments);
                /*list($path, $name, $attributes) = array_values($arguments);
                $this->{$type}($path, $name, $attributes);*/
                break;
        }

        return $this;
    }

    /**
     * Generate a link to a stylesheet file.
     *
     * <code>
     *   // Generate a link to a stylesheet file
     *   Asset::style('css/cygnite.css');
     * </code>
     *
     * @internal param $href
     * @internal param string $media
     * @internal param string $title
     *
     * @param $href
     * @param $media
     * @param $title
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function style($href, $media = "", $title = "")
    {
        $media = (is_null($media)) ? 'media=all' : $media;
        $title = (!is_null($title)) ? "title='$title'"  : '';

        if ( is_null($href) ) {
             throw new InvalidArgumentException("Cannot pass null argument to ".__METHOD__);
        }

        if ($this->hasRegExp($href)) {

            $stylePath= str_replace('\\', '/', $href);

            $styles = glob($stylePath.'*');
            foreach($styles as $style)
            {
                $this->assets[strtolower(__FUNCTION__)][] = (string)
                    static::$stylesheet.' '.$media.'
                    '.$title.' href="'.Url::getBase().$style.'" >'.PHP_EOL;
            }

            return $this->assets[strtolower(__FUNCTION__)];
        }

        list(, $caller) = debug_backtrace(false);

        // if method called statically via facade accessor we
        // will simply return the string
        if ($this->isFacade($caller)) {

            return $this->stripCarriage(static::$stylesheet.' '.$media.'
        '.$title.' href="'.Url::getBase().$href.'" >'.PHP_EOL);

        }

        $this->assets[strtolower(__FUNCTION__)][] = (string)
        static::$stylesheet.' '.$media.'
        '.$title.' href="'.Url::getBase().$href.'" >'.PHP_EOL;

        return $this->assets[strtolower(__FUNCTION__)];
    }

    private function hasRegExp($string)
    {
        return (strpos($string, '*') !== FALSE) ? true : false;
    }

    private function stripCarriage($string)
    {
        return trim(preg_replace('/\s\s+/', ' ', $string)).PHP_EOL;

    }

    public function dump($name)
    {
        foreach ($this->assets[$name] as $key => $value) {
            echo $this->stripCarriage($value);
        }
    }

    /**
     * Generate a link to a JavaScript file.
     *
     * <code>
     *  // Generate a link to a JavaScript file
     *    echo Asset::script('js/jquery.js');
     *
     * // Generate a link to a JavaScript file and add some attributes
     *   echo Asset::script('js/jquery.js', array('required'));
     * </code>
     *
     * @false  string  $url
     * @false  array   $attributes
     * @param       $url
     * @param array $attributes
     * @return string
     */
    protected function script($url, $attributes = array())
    {

        if ($this->hasRegExp($url)) {

            $scriptPath= str_replace('\\', '/', $url);

            $scripts = glob($scriptPath.'*');
            foreach($scripts as $script)
            {
                $this->assets[strtolower(__FUNCTION__)][] = (string)
                    static::$script.'
                    src="'.Url::getBase().$script.'"'.$this->addAttributes($attributes).'></script>'.PHP_EOL;
            }

            return $this->assets[strtolower(__FUNCTION__)];
        }

        list(, $caller) = debug_backtrace(false);

        if ($this->isFacade($caller)) {

            return $this->stripCarriage(static::$script.'
        src="'.Url::getBase().$url.'"'.$this->addAttributes($attributes).'></script>'.PHP_EOL);

        }


        $this->assets[strtolower(__FUNCTION__)][] =
        static::$script.'
        src="'.Url::getBase().$url.'"'.$this->addAttributes($attributes).'></script>'.PHP_EOL;

        return $this->assets[strtolower(__FUNCTION__)];
    }



    public function ajax($url, $data, $method, $callback, $callType = '')
    {
        $ajaxScript = '';
        $ajaxScript = '<script type="text/javascript">';
        $ajaxScript = '
                                $.ajax();
                                ';

        $ajax_script = '</script>';
    }

    private function isFacade($caller)
    {
        return (strpos($caller['file'], 'StaticResolver') !== FALSE) ? true : false;
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
    protected function link($url, $name = null, $attributes = array())
    {
        $name =  (is_null($name)) ? $url :  $name;

        list(, $caller) = debug_backtrace(false);

        if ($this->isFacade($caller)) {

            return $this->stripCarriage('<a href="'.Url::getBase().Html::entities($url).'"
         '.$this->addAttributes($attributes).'>'.Html::entities($name).'</a>'.PHP_EOL);

        }

        $this->assets[strtolower(__FUNCTION__)][] =
         '<a href="'.Url::getBase().Html::entities($url).'"
         '.$this->addAttributes($attributes).'>'.Html::entities($name).'</a>'.PHP_EOL;

        return $this->assets[strtolower(__FUNCTION__)];
    }

    /**
     * Form Html attributes from array.
     *
     * @false  array   $attributes
     * @param array $attributes
     * @param array $html
     * @return string
     */
    public function addAttributes($attributes, $html = array())
    {
        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                if (!is_null($value)) {
                    $html[] = $key.'="'.Html::entities($value).'"';
                }
            }
        }

        return (count($html) > 0) ? ' '.implode(' ', $html) : '';
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->assets[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->assets[$offset] : null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->assets[] = $value;
        } else {
            $this->assets[$offset] = $value;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->__unset[$offset]);
        }
    }

    /**
     * Unset an data by key
     *
     * @param string The key to unset
     * @access public
     */
    public function __unset($key)
    {
        unset($this->assets[$key]);
    }
}