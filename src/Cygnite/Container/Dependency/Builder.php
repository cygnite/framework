<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Container\Dependency;

use SplObjectStorage;
use Cygnite\Helpers\Inflector;
use Cygnite\Container\Reflection;
use Cygnite\Container\Exceptions\DependencyException;

/**
 * Class Builder.
 *
 * @package Cygnite\Container\Dependency
 */
abstract class Builder extends SplObjectStorage
{
    protected $definitions = [];

    public $controller = false;

    protected $controllersNs;

    protected $appNamespace;

    public $cache = [];

    protected $propertyDefinition;

    /**
     * Set application namespace for property and interface injection.
     *
     * @param $namespace
     * @return $this
     */
    public function setAppNamespace($namespace)
    {
        $this->appNamespace = $namespace;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppNamespace() : string
    {
        return $this->appNamespace;
    }

    /**
     * Set all definitions into array.
     *
     * @param $propertyInjections
     * @throws \Cygnite\Container\Exceptions\DependencyException
     * @return $this
     */
    public function setPropertyInjection($propertyInjections)
    {
        if (!is_array($propertyInjections)) {
            throw new DependencyException(__METHOD__.' only accept parameter as array.');
        }

        $namespace = $this->getAppNamespace();

        foreach ($propertyInjections as $controller => $properties) {
            foreach ($properties as $key => $value) {
                /*
                 | We will add key value pair into the definition array only if
                 | it is not already exists
                 */
                if (!isset($this->cache[$namespace.$controller][$key])) {
                    $this->definitions['\\'.$namespace.$controller][$key] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Set the service into container.
     *
     * @param $services
     * @throws \Exception
     * @return $this
     */
    public function setService($services)
    {
        if (!is_array($services)) {
            throw new \Exception(__METHOD__.' accept parameter as array.');
        }

        foreach ($services as $key => $alias) {
            $this[$key] = $alias;
        }

        return $this;
    }

    /**
     * Returns definitions by given key.
     *
     * @param null $key
     * @return array|null
     */
    public function getDefinitions($key = null)
    {
        if (!is_null($key)) {
            return isset($this->definitions[$key]) ? $this->definitions[$key] : null;
        }

        return !empty($this->definitions) ? $this->definitions : [];
    }

    /**
     * Set property definitions.
     *
     * @param $definition
     * @return $this
     */
    public function setPropertyDefinition($definition)
    {
        $this->propertyDefinition = $definition;

        return $this;
    }

    /**
     * @return null
     */
    public function getPropertyDefinition()
    {
        return isset($this->propertyDefinition) ? $this->propertyDefinition : null;
    }

    /**
     * @param $controller
     *
     * @return null
     */
    private function getPropertyDefinitionConfig($controller)
    {
        $injectableDefinitions = $this->getPropertyDefinition();

        return $this->setPropertyInjection($injectableDefinitions)
             ->getDefinitions($controller);
    }

    /**
     * Inject all your properties into controller at run time.
     *
     * @param $classInstance
     * @param $controller
     * @throws \Exception
     * @return bool
     */
    public function propertyInjection($classInstance, $controller)
    {
        $dependencies = $this->getPropertyDefinitionConfig($controller);
        $controller = "\\\\";

        if (array_key_exists($controller, $this->definitions)) {

            list($reflection, $reflectionClass) = $this->setReflectionClassAttributes($controller);
            
            foreach ($dependencies as $classProperty => $class) {
                $reflectionArray = [$reflectionClass, $classProperty];
                list($object, $controllerProp) = $this->checkPropertyAndMakeObject($controller, $class, $reflectionArray);
                
                /*
                 | We will check is set{PropertyName}() method exists in class.
                 | If exists we will call the method to set object into it
                 */
                if (method_exists($classInstance, 'set'.$controllerProp)) {
                    $classInstance->{'set'.$controllerProp}($object);
                } else {
                    $prop = $reflectionClass->getProperty($classProperty);
                    /*
                     | Check if property defined as static.
                     | we will throw exception is property defined as static
                     */
                    if ($prop->isStatic()) {
                        throw new DependencyException(
                            sprintf("Static Property '%s' is not injectable in $controller controller", $classProperty)
                        );
                    }

                    $this->setPropertyValue($reflection, $classInstance, $classProperty, $object);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Set property value.
     *
     * @param $reflection
     * @param $classInstance
     * @param $classProperty
     * @param $object
     */
    protected function setPropertyValue($reflection, $classInstance, $classProperty, $object)
    {
        /*
         | We will make property accessible and set the value into it
         */
        $reflection->makePropertyAccessible($classProperty);
        $reflectionProperty = $reflection->getReflectionProperty();
        $reflectionProperty->setValue($classInstance, $object);
    }

    /**
     * @param $controller
     * @param $class
     * @param $reflectionArray
     *
     * @throws \Cygnite\Container\Exceptions\DependencyException
     * @return array
     */
    private function checkPropertyAndMakeObject($controller, $class, $reflectionArray)
    {
        list($reflectionClass, $classProperty) = $reflectionArray;

        if (!$reflectionClass->hasProperty($classProperty)) {
            throw new DependencyException(
                sprintf("Property %s is not defined in $controller controller", $classProperty)
            );
        }

        $controllerProp = Inflector::classify($classProperty);
        $object = $this->make($class);

        return [$object, $controllerProp];
    }

    /**
     * @param $class
     * @return array
     */
    private function setReflectionClassAttributes($class) : array
    {
        $reflection = new Reflection();
        $reflection->setClass($class);

        return [$reflection, $reflection->getReflectionClass()];
    }
}
