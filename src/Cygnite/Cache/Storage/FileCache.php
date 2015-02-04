<?php
namespace Cygnite\Cache\Storage;

use Cygnite\Helpers\Config;
use Cygnite\Cache\StorageInterface;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *    http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package            : Cygnite Framework File caching system
 * @Filename           : File
 * @Description        : This library is used to cache your web page and store into file format.
 *                       Part of the library is ispired by simple cache class.
 * @Author             : Sanjoy Dey
 * @Copyright          : Copyright (c) 2013 - 2014,
 * @Link               : http://www.cygniteframework.com
 * @Since              : Version 1.0
 * @Filesource
 * @Warning            : Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class FileCache
{
    /**
    * The path to the cache file folder
    *
    * @var string
    */
    private $_cachePath;

    /**
    * The name of the default cache file
    *
    * @var string
    */
    private $_cacheName = 'default';

    /**
    * The cache file extension
    *
    * @var string
    */
    private $_extension = '.tmp';


    public function __construct()
    {
        Config::get('global.config', 'cache_name');

        $cache_config = array(
           'name' => Config::get('global.config', 'cache_name'),
           'path' => Config::get('global.config', 'cache_directory'),
           'extension' => Config::get('global.config', 'cache_extension')
        );

        $this->initialize($cache_config);

        if (Config::get('global.config', 'cache_directory') == "none") {
            throw new \Exception('You must define cache directory to use cache.');
        } else {
            $this->_cachePath = APPPATH.Config::get('global.config', 'cache_directory').'/';
        }
    }

    public function initialize($config = array())
    {
        if (isset($config) === true) {
            if (is_string($config)) {
                    $this->setCache($config);
            } elseif (is_array($config)) {
                    $this->setCache($config['name']);
                    $this->setPath($config['path']);
                    $this->setCacheExtension($config['extension']);
            }
        }
    }

    public function isCached($key)
    {
        if ($this->getCache() != false) {
            $cachedData = $this->getCache();
            return isset($cachedData[$key]['data']);
        }
    }

    public function getTimeout()
    {
        return (int)ini_get('session.gc_maxlifetime');
    }

    /**
     * Save data into cache
     *
     * @false string
     * @false mixed
     * @false integer [optional]
     * @param     $key
     * @param     $value
     * @param int $expiration
     * @return object
     */
    public function save($key, $value, $expiration = 0)
    {
        // $this->getTimeout(); Do delete based on the session time out
        $data = array(
                  'time'   => time(),
                  'expire' => $expiration,
                  'data'   => $value
        );

        if (is_array($this->getCache())) {
            $dataArray = $this->getCache();
            $dataArray[$key] = $data;
        } else {
            $dataArray = array($key => $data);
        }

        $cacheData = json_encode($dataArray);

        if ($this->getCacheDirectory() == true) {
            @file_put_contents($this->getCacheDirectory(), $cacheData);
        }

        return $this;

    }

    /**
     * Retrieve cache value from file by key
     *
     * @false string
     * @false boolean [optional]
     * @param      $key
     * @param bool $timestamp
     * @return string
     */
    public function fetch($key, $timestamp = false)
    {
        $cachedData = array();

        $cachedData = $this->getCache();

        if ($timestamp === false) {
            return $cachedData[$key]['data'];
        } else {
            return $cachedData[$key][ 'time'];
        }
    }

    private function getCache()
    {
        if (file_exists($this->getCacheDirectory())) {
            return json_decode(
                file_get_contents(
                    $this->getCacheDirectory()
                ),
                true
            );
        } else {
            return false;
        }
    }

    private function getCacheDirectory()
    {
        if ($this->hasCacheDir() === true) {
            $file_name = $this->getCacheName();
            $filename = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($file_name));

            return $this->getPath().md5($filename).$this->getCacheExtension();
        }
    }


    public function hasCacheDir()
    {
        if (!is_dir($this->getPath()) && !mkdir($this->getPath(), 0775, true)) {
            throw new \Exception('Unable to create cache directory ');
        } elseif (!is_readable($this->getPath()) ||
            !is_writable($this->getPath())
        ) {
            if (!chmod($this->getPath(), 0775)) {
                throw new \Exception(
                    'Cache Path Error '.$this->getPath() . ' directory must be writeable'
                );
            }

        }
        return true;
    }

    private function getPath()
    {
        return $this->_cachePath;
    }

    public function setPath($pathUrl)
    {
        $this->_cachePath = $pathUrl;

        return $this;
    }

    public function setCache($name)
    {
        $this->_cacheName = $name;
        
        return $this;
    }

    /**
     * get cache name
     *
     * @return string
     */
    public function getCacheName()
    {
         return $this->_cacheName;
    }

    public function setCacheExtension($ext)
    {
        $this->_extension = $ext;
        
        return $this;
    }

    /**
    * Cache file extension Getter
    *
    * @return string
    */
    public function getCacheExtension()
    {
         return $this->_extension;
    }

    private function isExpired($timestamp, $expiration)
    {
        $result = false;
        if ($expiration !== 0):
            $timeDiff = time() - $timestamp;
            ($timeDiff > $expiration) ? $result = true : $result = false;
        endif;
        return $result;
    }

    public function deleteExpiredCache()
    {
        $cacheData = $this->getCache();

        if (true === is_array($cacheData)) {
              $counter = 0;
            foreach ($cacheData as $key => $entry) {

                if (true === $this->isExpired($entry['time'], $entry['expire'])) {
                    unset($cacheData[$key]);
                    $counter++;
                }

            }

            if ($counter > 0) {
                $cacheData = json_encode($cacheData);
                @file_put_contents($this->getCacheDirectory(), $cacheData);
            }

            return $counter;
        }
    }

    /**
    * Erase all cached entries
    *
    * @return object
    */
    public function deleteAllCache()
    {
        $cache_dir = $this->getCacheDirectory();

        if (file_exists($cache_dir)) {
            $cache_file = fopen($cache_dir, 'w');
            fclose($cache_file);
        }

        return $this;
    }
}
