<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Foundation;

use Cygnite\Common\File\FileExtensionFilter;
use Cygnite\Exception;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class AutoLoader
{
    private $instance = [];

    private $directories = [];

    public $loadedClass = [];

    private $inflection;

    /**
     * Autoloader Constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize SPL AutoLoader.
     */
    protected function init()
    {
        spl_autoload_unregister([$this, 'autoLoad']);
        spl_autoload_extensions('.php');
        spl_autoload_register([$this, 'autoLoad']);
    }

    private static function changeCase($string, $isLower = false)
    {
        return ($isLower === false) ? $string : ucfirst($string);
    }

    /**
     *----------------------------------------------------------
     * Auto load all classes
     *----------------------------------------------------------
     * All classes will get auto loaded here.
     *
     * @param $className
     *
     * @throws \Exception
     *
     * @internal param string $className
     *
     * @return bool
     */
    private function autoLoad($className)
    {
        $path = $rootDir = '';
        //$fileName = $this->psr0AutoLoader($className);

        if (array_key_exists($className, $this->directories)) {
            try {
                if (is_readable($this->directories[$className])) {
                    return include CYGNITE_BASE.DS.str_replace(
                            ['\\\\', '\\'],
                            DS,
                            str_replace('/', DS, $this->directories[$className])
                        );
                } else {
                    throw new \Exception("Requested file $this->directories[$className] not found!!");
                }
            } catch (Exception $ex) {
                throw new \Exception("Error occurred while loading class $className".$ex->getMessage());
            }
        }
    }

    public static function psr0AutoLoader($className)
    {
        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            if (preg_match(
                '/Apps/i',
                $namespace
            )
            ) {
                $fileName = str_replace('\\', DS, $namespace).DS;
            } else {
                $fileName = 'vendor'.DS.'cygnite'.DS.'src'.DS.str_replace('\\', DS, $namespace).DS;
            }
        }

        $fileName .= str_replace('_', DS, $className).'.php';

        return $fileName;
    }

    /*
    * Call magic method to register classes and models dynamically into cygnite engine
    * @param $name method name
    * @args $args array passed into the method
    */
    public function __call($method, $args)
    {
        if ($method == 'registerDirectories') {
            return call_user_func_array([$this, 'setDirectories'], $args);
        } else {
            throw new \Exception("Invalid method $method");
        }
    }

    /**
     * Register all your directories in order to auto load.
     *
     * @param $paths
     */
    private function setDirectories($paths)
    {
        if (!empty($paths)) {
            foreach ($paths as $key => $dir) {
                $path = str_replace('.', DS, $dir);

                //Iterate through all paths and filter with extension provided
                $recursiveExtensionFilter = new FileExtensionFilter(
                    new \RecursiveDirectoryIterator($path)
                );

                // loop through the directory listing
                // we need to create a RecursiveIteratorIterator instance
                foreach ($recursiveExtensionFilter as $item) {
                    $alias = str_replace('.php', '', $item->getPathName());

                    $alias = implode('\\', array_map('ucfirst', explode(DS, $alias)));
                    if (!isset($this->directories[$alias])) {
                        $this->directories[str_replace('Src', '', $alias)] = str_replace('\\', '/', $item->getPathName());
                    }
                }
            }
        }
    }

    /**
     * --------------------------------------------------------------------
     * Import application files
     * --------------------------------------------------------------------
     * This method is used to import application
     * and system helpers and plugins.
     *
     * @param $path
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function import($path)
    {
        if (is_null($path)) {
            throw new \InvalidArgumentException('Empty path passed on '.__METHOD__);
        }

        $dirPath = null;
        $dirPath = CYGNITE_BASE.DS.str_replace('.', DS, $path).EXT;

        if (!is_readable($dirPath) && !file_exists($dirPath)) {
            throw new \Exception("Requested file doesn't exist in following path $dirPath ".__METHOD__);
        }

        return include $dirPath;
    }

    /**
     * -----------------------------------------------------------------
     * Get all loaded classes
     * -----------------------------------------------------------------
     * This method is used to return all registered class names
     * from cygnite robot.
     *
     *@return array
     */
    public function registeredClasses()
    {
        return $this->instance;
    }
}
