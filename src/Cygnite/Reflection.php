<?php
namespace Cygnite;

use ReflectionClass;
use ReflectionProperty;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Reflection
 * Reflection class is used to get reflection class variables
 * and make accessible for callee
 * @package Cygnite
 */
class Reflection
{
    public $reflectionClass;

    //properties
    private $properties;

    public $reflectionProperty;

    /**
     * Get instance of your class using Reflection api
     *
     * @access public
     * @param  $class
     * @throws \Exception
     * @return object
     */
    public static function getInstance($class= null)
    {
        $reflector = null;

        if (class_exists($class)) {
           throw new \Exception(sprintf("Class %s not found", $class));
        }

        $reflector = new ReflectionClass('\\'.$class);

        return new $reflector->name;
    }

    /**
     * Set your class to reflection api
     *
     * @access public
     * @param  $class
     * @return $this
     */
    public function setClass($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
    }

        $this->reflectionClass = new ReflectionClass($class);

        return $this;
    }

    /**
     * @return null
     */
    public function getReflectionClass()
    {
        return (isset($this->reflectionClass) ? $this->reflectionClass : null);
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
        $reflectionProperty = $this->getReflectionClass()->getProperty($property);
        $this->setReflectionProperty($reflectionProperty);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($this->getReflectionClass());
    }

    /**
     * @param $property
     */
    public function setReflectionProperty($property)
    {
        $this->reflectionProperty = $property;
    }

    /**
     * @return null
     */
    public function getReflectionProperty()
    {
        return (isset($this->reflectionProperty) ? $this->reflectionProperty : null);
    }
}
