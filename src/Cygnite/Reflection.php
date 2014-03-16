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
 * @Package             :  Packages
 * @Sub Packages        :
 * @Filename            :  Reflection
 * @Description         :  Reflection class is used to get reflection class variables and make accessible for callea
 * @Author              :  Sanjoy Dey
 * @Copyright           :  Copyright (c) 2013 - 2014,
 * @Link	            :  http://www.cygniteframework.com
 * @Since	            :  Version 1.0
 * @Filesource
 *
 *
 */

class Reflection
{
	private $reflectionClass;
	
	//properties
	private $properties;

    /**
	 * Get instance of your class using Reflection api
	 * 
	 * @access public 
	 * @param  $class
	 * @return object
	 * 
	 */
    public static function getInstance($class= null)
    {
        $reflector = null;
        
        if (class_exists($class)) {
            $reflector = new \ReflectionClass('\\'.$class);

            return new $reflector->name;
        }
    }
	
	/**
	 * Set your class to reflection api
	 * 
	 * @access public 
	 * @param  $class
	 * @return void
	 * 
	 */
	public function setClass($class)
	{
		$this->reflectionClass = new \ReflectionClass(get_class($class));		
	}
	
	/**
	 * Make your protected or private property accessible
	 * 
	 * @access public 
	 * @param  $property
	 * @return string/ int property value
	 * 
	 */
	public function makePropertyAccessible($property)
	{
		$reflectionProperty = $this->reflectionClass ->getProperty($property);
        $reflectionProperty->setAccessible(true);
        
        return $reflectionProperty->getValue($this->reflectionClass);	
	}
}
