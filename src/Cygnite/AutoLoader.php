<?php
namespace Cygnite;

use Cygnite\Libraries\FileExtensionFilter;

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
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 *@package                    :  Packages
 *@subpackages                :  Base
 *@filename                   :  AutoLoader
 *@description                :  This is registry auto loader for CF
 *@author                     :  Sanjoy Dey
 *@copyright                  :  Copyright (c) 2013 - 2014,
 *@link	                      :  http://www.cygniteframework.com
 *@since	                  :  Version 1.0
 *@filesource
 *
 */

class AutoLoader
{
    private $instance = array();

    private $directories = array();

    public $loadedClass = array();

    private $inflection;

    private function __construct()
    {

    }

    protected function init($inflection)
    {
        $this->inflection = $inflection;
        spl_autoload_unregister(array($this, 'autoLoad'));
        spl_autoload_extensions(".php");
        spl_autoload_register(array($this, 'autoLoad'));
    }

    private static function changeCase($string, $isLower = false)
    {
        return ($isLower === false)
            ? $string
            : ucfirst($string);
    }

    /**
     *----------------------------------------------------------
     * Auto load all classes
     *----------------------------------------------------------
     * All classes will get auto loaded here.
     *
     * @param $className
     * @throws \Exception
     * @internal param string $className
     * @return boolean
     */
    private function autoLoad($className)
    {

        $path  = $rootDir ='';
        //$exp = explode('\\', $className);
        //show(end($exp));
        //$fileName = $this->psr0AutoLoader($className);

        if (array_key_exists($className, $this->directories)) {
            try {
                if (is_readable($this->directories[$className])) {

                    return include CYGNITE_BASE.DS.str_replace(
                            array(
                                '\\\\',
                                '\\'
                            ), DS, str_replace('/', DS, $this->directories[$className])
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
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            if (preg_match(
                "/Apps/i",
                $namespace
            )
            ) {
                $fileName  = strtolower(str_replace('\\', DS, $namespace) . DS);
            } else {
                $fileName  = 'vendor'.DS.'cygnite'.DS.'src'.DS.str_replace('\\', DS, $namespace) . DS;
            }

        }

        $fileName .= str_replace('_', DS, $className) . '.php';

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
            return call_user_func_array(array($this, 'setDirectories'), $args);
        } else {
            throw new \Exception("Invalid method $method");
        }
    }

    private function setDirectories($paths)
    {
        foreach ($paths as $key => $dir)
        {
            $path = str_replace(".", DS, $dir);

	    //Iterate through all paths and filter with extension provided
	    $recursiveExtensionFilter = new FileExtensionFilter(new \RecursiveDirectoryIterator($path));

            // loop through the directory listing
            // we need to create a RecursiveIteratorIterator instance
            foreach ($recursiveExtensionFilter as $item) {
               $alias = str_replace('.php', '', $item->getPathName());

               $alias = implode("\\", array_map("ucfirst", explode(DS, $alias)));
               $this->directories[$alias] = str_replace('\\', '/', $item->getPathName());
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
    * @throws \Exception
    * @return bool
    */
    public static function import($path)
    {
        if (is_null($path) || $path == "") {
            throw new \InvalidArgumentException("Empty path passed on ".__METHOD__);
        }

        $dirPath = null;
        $dirPath = CYGNITE_BASE.DS.str_replace('.', DS, $path).EXT;

        if (is_readable($dirPath) && file_exists($dirPath)) {
            return include_once $dirPath;
        } else {
            throw new \Exception("Requested file doesn't exist in following path $dirPath ".__METHOD__);
        }
    }

    /**
    * ----------------------------------------------------------
    * Request a object of the class
    * ----------------------------------------------------------
    * This method is used to request classes from
    * Cygnite Engine and  return library object
    *
    * @param $key string
    * @param $val NULL
    * @throws \Exception
    * @return object
    */
    public function request($key, $val = null)
    {
        $class = $libPath = "";

        if (!array_key_exists(ucfirst($key), self::$_classNames)) {
            throw new \Exception("Requested $class Library not exists !!");
        }

        $class = self::$_classNames[ucfirst($key)];
        $libPath = getcwd().DS.CF_SYSTEM.strtolower(
            str_replace(
                array(
                     '\\',
                     '.',
                     '>'
                ),
                DS,
                $class
            )
        ).EXT;

        if (is_readable($libPath) && class_exists($class)) {
            if (!isset(self::$instance[$class])) {
                self::$instance[$class] = new $class($val);
            }

            return self::$instance[$class];
            // You cannot pass parameters to constructor of the class
        } else {
            throw new \Exception("Requested class not available on $libPath");
        }
    }


    /**
    * -----------------------------------------------------------------
    * Get all loaded classes
    * -----------------------------------------------------------------
    * This method is used to return all registered class names
    * from cygnite robot
    *@return array
    */
    public function registeredClasses()
    {
        return $this->instance;
    }

    /**
     * -------------------------------------------------------------------
     * Load models and return model object
     * -------------------------------------------------------------------
     * This method is used to load all models dynamically
     * and return model object
     *
     * @param $key string
     * @param $val string
     * @throws \Exception
     * @return object
     */
    public function model($key, $val = null)
    {
        $class = $libPath = "";
        $class = ucfirst(trim($key));

        if (!array_key_exists($class, self::$_classNames)) {
            throw new \Exception("Requested $class Library not exists !!");
        }

        $libPath = strtolower(APPPATH).DS.'models'.DS;

        if (is_readable($libPath) && class_exists($class)) {
            return new $class();
        } else {
            throw new \Exception("Path not readable $libPath");
        }

    }
}
