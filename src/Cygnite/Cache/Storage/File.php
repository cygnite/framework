<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Cache\Storage;

use Cygnite\Helpers\Config;
use Cygnite\Cache\StorageInterface;
use Cygnite\Cache\Exceptions\InvalidCacheDirectoryException;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Cygnite File Cache
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class File implements StorageInterface
{
    /**
    * The path to the cache file folder
    *
    * @var string
    */
    private $cachePath;

    /**
    * The name of the default cache file
    *
    * @var string
    */
    private $cacheName = 'default';

    /**
    * The cache file extension
    *
    * @var string
    */
    private $extension = '.tmp';

    private $where = false;


    /**
     * Constructor of File Cache
     * We will initialize file cache
     */
    public function __construct()
    {
        $config = array(
           'name' => Config::get('global.config', 'cache_name'),
           'path' => Config::get('global.config', 'cache_directory'),
           'extension' => Config::get('global.config', 'cache_extension')
        );

        if ($config['path'] == "") {
            throw new InvalidCacheDirectoryException('You must define cache directory to use cache.');
        }

        $this->initialize($config);
    }

    /**
     * @param array $config
     */
    public function initialize($config = array())
    {
        $path = str_replace(array('.', '/'), DS, $config['path']);

        if (isset($config) === true) {
            if (is_string($config)) {
                $this->setCache($config);
            } elseif (is_array($config)) {
                $this->setCache($config['name']);
                $this->setPath(APPPATH.DS.$path.DS);
                $this->setCacheExtension($config['extension']);
            }
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function isCached($key)
    {
        if ($this->getCache() != false) {
            $cached = $this->getCache();
            return isset($cached[$key]['data']);
        }
    }

    /**
     * @return int
     */
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
    public function store($key, $value, $expiration = 0)
    {
        // $this->getTimeout(); Do delete based on the session time out
        $data = array(
                  'time'   => time(),
                  'expire' => $expiration,
                  'data'   => $value
        );

        if ($this->where == true) {
           $this->setCache($key);
        }

        if (is_array($this->getCache())) {
            $dataArray = $this->getCache();
            $dataArray[$key] = $data;
        } else {
            $dataArray = array($key => $data);
        }

        $cacheData = json_encode($dataArray);

        if ($this->getDirectory() == true) {
            @file_put_contents($this->getDirectory(), $cacheData);
        }

        return $this;

    }

    /**
     * Checking cache existence
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        if ($this->where == true) {
            $this->setCache($key);
        }

        $cached = $this->getCache();
        return !empty($cached[$key]) ? true : false;
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
    public function get($key, $timestamp = false)
    {
        if ($this->where == true) {
            $this->setCache($key);
        }

        $cached = array();
        $cached = $this->getCache();

        if ($timestamp === false) {
            return $cached[$key]['data'];
        } else {
            return $cached[$key][ 'time'];
        }
    }

    /**
     * @return bool|mixed
     */
    private function getCache()
    {
        if (file_exists($this->getDirectory())) {
            return json_decode(
                file_get_contents(
                    $this->getDirectory()
                ),
                true
            );
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    private function getDirectory()
    {
        if ($this->hasDirectory() === true) {
            $fileName = $this->getCacheName();
            $fileName = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($fileName));

            return $this->getPath().md5($fileName).$this->getCacheExtension();
        }
    }


    /**
     * @return bool
     * @throws \Exception
     */
    public function hasDirectory()
    {
        if (!is_dir($this->getPath()) && !mkdir($this->getPath(), 0775, true)) {
            throw new InvalidCacheDirectoryException('Unable to create cache directory ');
        } elseif (
            !is_readable($this->getPath()) ||
            !is_writable($this->getPath())
        ) {
            if (!chmod($this->getPath(), 0775)) {
                throw new InvalidCacheDirectoryException(
                    'Cache Path Error '.$this->getPath() . ' directory must be writable'
                );
            }

        }
        return true;
    }

    /**
     * @param $pathUrl
     * @return $this
     */
    public function setPath($pathUrl)
    {
        $this->cachePath = $pathUrl;

        return $this;
    }

    /**
     * @return string
     */
    private function getPath()
    {
        return $this->cachePath;
    }

    /**
     * @param $name
     * @return $this
     * @return $this
     */
    public function setCache($name)
    {
        $this->cacheName = $name;

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function where($name)
    {
        $this->where = true;

        return $this->setCache($name);
    }

    /**
     * @param $method
     * @param $arguments
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ($method == 'as') {
            return call_user_func_array(array($this, 'where'), array($arguments));
        }

        throw new \BadMethodCallException("Invalid method called File::$method");
    }

    /**
     * get cache name
     *
     * @return string
     */
    public function getCacheName()
    {
         return $this->cacheName;
    }

    /**
     * @param $ext
     * @return $this
     */
    public function setCacheExtension($ext)
    {
        $this->extension = $ext;

        return $this;
    }

    /**
    * Cache file extension Getter
    *
    * @return string
    */
    public function getCacheExtension()
    {
         return $this->extension;
    }

    /**
     * @param $timestamp
     * @param $expiration
     * @return bool
     */
    private function isExpired($timestamp, $expiration)
    {
        $result = false;
        if ($expiration !== 0) {
            $timeDiff = time() - $timestamp;
            $result =($timeDiff > $expiration) ? true :  false;
        }

        return $result;
    }

    /**
     * We will destroy expired cache from the directory
     *
     * @return int
     */
    public function destroyExpiredCache()
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
                @file_put_contents($this->getDirectory(), $cacheData);
            }

            return $counter;
        }
    }

    public function destroy($key)
    {

    }

    /**
    * Erase all cached entries
    *
    * @return object
    */
    public function destroyAll()
    {
        $cacheDir = $this->getDirectory();

        if (file_exists($cacheDir)) {
            $cacheFile = fopen($cacheDir, 'w');
            fclose($cacheFile);
        }

        return $this;
    }
}
