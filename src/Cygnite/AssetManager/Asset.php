<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\AssetManager;

use Cygnite\Proxy\StaticResolver;
use Cygnite\AssetManager\Html;
use Cygnite\Common\UrlManager\Url;
use InvalidArgumentException;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite Asset Manager
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 * <code>
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
 * </code>
 */

class Asset extends StaticResolver implements \ArrayAccess
{
    public static $directoryName = 'assets';
    private static $stylesheet = '<link rel="stylesheet" type="text/css"';
    private static $script = '<script type="text/javascript"';
    protected $assets = array();
    protected $combinedAssets = array();
    protected $tag = array();
    private $where = 'default';
    private $baseUrl;
    private $assetDirectory;
    private $external = false;
    private $combine = false;
    private $paths = array();

    /**
     * We will check if external true,
     * if so then we will set the base url as empty
     * so that user can give his own path to load assets
     *
     * @param bool $flag
     * @return $this
     */
    public function isExternal($flag = false)
    {
        $this->external = true;

        if ($flag) {
            $this->baseUrl = '';
        }

        return $this;
    }

    /**
     * @param       $type
     * @param array $arguments
     * @return $this
     */
    public function add($type, $arguments = array())
    {
        /*
         | By default we will tag all assets to 'default' index
         */
        if ($this->where == 'default') {
            $this->where('default');
        }

        switch ($type) {
            case 'style':
                call_user_func_array(array($this, $type), $arguments);
                break;
            case 'script':
                call_user_func_array(array($this, $type), $arguments);
                break;
            case 'link':
                call_user_func_array(array($this, $type), $arguments);
                break;
        }

        return $this;
    }

    /**
     * Set where to group collection of assets together
     * By tagging assets into key we can easily find out
     * which collection user is requesting to
     *
     * Example:
     *
     * $asset->where('header')->add('style', array('path' => ''));
     * $asset->where('footer')->add('style', array('path' => ''));
     *
     * $asset->where('header')->dump('style');
     *
     * @param $key
     * @return $this
     */
    public function where($key)
    {
        $this->tag[$key] = $key;

        return $this;
    }

    /**
     * We will render all assets into browser
     *
     * @param $name
     * @return void
     */
    public function dump($name)
    {
        // Check {style.final} and display only combined asset into browser
        if ($this->combine && string_has($name, '.') && isset($this->combinedAssets[$this->tag[$this->where]][$name])) {
            $this->render($this->combinedAssets[$this->tag[$this->where]][$name]);

        } else {
            if (isset($this->assets[$this->tag[$this->where]][$name])) {
                $this->render($this->assets[$this->tag[$this->where]][$name]);
            }
        }
    }

    /**
     * Render assets into browser
     *
     * @param $data
     */
    private function render($data)
    {
        foreach ($data as $key => $asset) {
            echo $this->stripCarriage($asset) . PHP_EOL;
        }
    }

    /**
     * @param $name
     */
    public function setAssetDir($name)
    {
        $this->assetDirectory = $name;
    }

    /**
     * We will combine all assets tagged to the given key
     * and make a final file which will contain all asset
     * source
     *
     * $asset->add('style', array('path' => ''))
     *       ->add('style', array('path' => ''))
     *       ->combine('style', 'final_css', 'assets/css/final.css');
     *
     * @param      $name
     * @param      $path
     * @param      $file
     * @param bool $compress
     * @return $this
     */
    public function combine($name, $path, $file, $compress = false)
    {
        $this->combine = true;

        if (file_exists(CYGNITE_BASE . DS . $path . $file)) {

            $cssAsset = file_get_contents(CYGNITE_BASE . DS . $path . $file);
            if (string_has($cssAsset, '@generator')) {
                return $this;
            }
        }

        $filePointer = fopen(CYGNITE_BASE . DS . $path . $file, "w")
        or die("Please set folder " . CYGNITE_BASE . DS . $path . $file . " permission to 777.");

        $content = "
        /**\n
         | Import All CSS Assets here \n
         | @generator Cygnite AssetManager\n
         */\n\n";
        foreach ($this->paths[$this->tag[$this->where]][$name] as $key => $src) {

            if ($name == 'style') {
                $content .= $this->combineStylesheets($src, $compress);
            } else if ($name == 'script') {
                $content .= $this->combineScripts($src, $compress);
            }
        }

        fwrite($filePointer, $content);
        $content = '';
        fclose($filePointer);

        $assetName = trim($this->getNameFromPathInfo($file));

        $styleTag = '';
        $styleTag = static::$stylesheet . ' title="' . $file . '" href="' . $this->getBaseUrl(
            ) . $path . $file . '" >' . PHP_EOL;

        $this->combinedAssets[$this->tag[$this->where]][$name . '.' . $assetName][] = (string)$styleTag;

        return $this;
    }

    /**
     * We will combine stylesheets and return contents
     *
     * @param $src
     * @param $compress
     * @return string
     */
    private function combineStylesheets($src, $compress)
    {
        return $this->compressSource($src, $compress);
    }

    /**
     * We will compress content is $compress = true else
     * simply we will append content into the final resource
     *
     * @param $src
     * @param $compress boolean
     * @return string
     */
    private function compressSource($src, $compress)
    {
        $content = $assetContent = '';
        $assetContent = @file_get_contents(CYGNITE_BASE . DS . $src);
        $content .= ($compress) ?
            compress($assetContent) . PHP_EOL :
            $assetContent . PHP_EOL;

        return $content;
    }

    /**
     * @param $src
     * @param $compress
     * @return string
     */
    private function combineScripts($src, $compress)
    {
        return $this->compressSource($src, $compress);
    }

    /**
     * We will get the file information from pathinfo()
     *
     * @param        $src
     * @param string $type
     * @return mixed
     */
    private function getNameFromPathInfo($src, $type = 'filename')
    {
        $assets = pathinfo($src);
        return ($type == 'filename') ? $assets[$type] : str_replace($this->getAssetDirName(), '', $assets[$type]);
    }

    /**
     * @return string
     */
    public function getAssetDirName()
    {
        return isset($this->assetDirectory) ? $this->assetDirectory : static::$directoryName;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
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
     * Offset to set
     *
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
     *
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
        $title = (!is_null($title)) ? "title= '$title'" : '';

        $this->setLocation($href, strtolower(__FUNCTION__));

        if (is_null($href)) {
            throw new InvalidArgumentException("Style path cannot be null.");
        }

        // Check if we regular expression exists
        if ($this->hasRegExp($href)) {
            //We will include all style sheets from the given directory
            $this->loadAssetsFromDir($href, $media, $title, 'style');

            return $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)];
        }

        list(, $caller) = debug_backtrace(false);

        $styleTag = '';
        $styleTag = static::$stylesheet . ' ' . $media . '
                   ' . $title . ' href="' . $this->getBaseUrl() . $href . '" >' . PHP_EOL;

        /*
         | If method called statically we will simply return
         | string
         */
        if ($this->isFacade($caller)) {

            return $this->stripCarriage($styleTag);
        }

        $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)][] = (string)$styleTag;

        return $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)];
    }

    /**
     * @param $path
     * @param $name
     * @return $this
     */
    private function setLocation($path, $name)
    {
        $this->paths[$this->where][$name][] = $path;

        return $this;
    }

    /**
     * @param $string
     * @return bool
     */
    private function hasRegExp($string)
    {
        return (strpos($string, '*') !== false) ? true : false;
    }

    /**
     * Include all the stylesheets from the path
     *
     * @param        $href
     * @param        $attr
     * @param        $title
     * @param string $type
     * @return void
     */
    private function loadAssetsFromDir($href, $attr, $title, $type = 'style')
    {
        $path = str_replace('\\', '/', $href);
        $assets = glob($path . '*');

        foreach ($assets as $src) {
            ($type == 'style') ? $this->setStyle($attr, $src, $title) : $this->setScript($src, $attr);
        }
    }

    /**
     * We will set the styles into assets array
     *
     * @param $media
     * @param $style
     * @param $title
     * @return void
     */
    private function setStyle($media, $style, $title)
    {
        $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)][] = (string)
            static::$stylesheet . ' ' . $media . '
                    ' . $title . ' href="' . $this->getBaseUrl() . $style . '" >' . PHP_EOL;
    }

    /**
     * get the base url
     */
    public function getBaseUrl()
    {
        if ($this->external == false) {
            return $this->baseUrl = Url::getBase();
        }

        return $this->baseUrl;
    }

    /**
     * We will set the script into assets array
     *
     * @param $src
     * @param $attributes
     */
    private function setScript($src, $attributes)
    {
        $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)][] = (string)
            static::$script . '
                    src="' . Url::getBase() . $src . '"' . $this->addAttributes($attributes) . '></script>' . PHP_EOL;
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
                    $html[] = $key . '="' . Html::entities($value) . '"';
                }
            }
        }

        return (count($html) > 0) ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Check if user is calling functions statically then
     * We will access methods using StaticResolver
     *
     * @param $caller
     * @return bool
     */
    private function isFacade($caller)
    {
        return (strpos($caller['file'], 'StaticResolver') !== false) ? true : false;
    }

    /**
     * @param $string
     * @return string
     */
    private function stripCarriage($string)
    {
        return trim(preg_replace('/\s\s+/', ' ', $string)) . PHP_EOL;
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
        $this->setLocation($url, strtolower(__FUNCTION__));

        // Check if regular expression exists
        if ($this->hasRegExp($url)) {
            // Include all the assets from the directory
            $this->loadAssetsFromDir($url, $attributes, '', 'script');

            return $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)];
        }

        list(, $caller) = debug_backtrace(false);

        $scriptTag = '';
        $scriptTag = static::$script . '
                src="' . $this->getBaseUrl() . $url . '"' . $this->addAttributes($attributes) . '></script>' . PHP_EOL;

        /*
        | If method called statically we will simply return
        | as string
        */
        if ($this->isFacade($caller)) {

            return $this->stripCarriage($scriptTag);
        }

        $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)][] = $scriptTag;

        return $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)];
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
        $name = (is_null($name)) ? $url : $name;
        $this->setLocation($url, strtolower(__FUNCTION__));

        list(, $caller) = debug_backtrace(false);

        $lingTag = '';
        $lingTag = '<a href="' . $this->getBaseUrl() . Html::entities($url) . '"
         ' . $this->addAttributes($attributes) . '>' . Html::entities($name) . '</a>' . PHP_EOL;

        /*
        | If method called statically we will simply return
        | as string
        */
        if ($this->isFacade($caller)) {

            return $this->stripCarriage($lingTag);
        }

        $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)][] = $lingTag;

        return $this->assets[$this->tag[$this->where]][strtolower(__FUNCTION__)];
    }
}