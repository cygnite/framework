<?php
namespace Cygnite\DependencyInjection;

use SplObjectStorage;
use Cygnite\Reflection;
use Cygnite\Helpers\Inflector;

class DependencyExtension extends SplObjectStorage
{
    public $definitions = array();

    public $factory = array();

    public $controller = false;

    public $namespace = '\\Controllers\\';

    /**
     * Set all definitions into array
     * @param $propertyInjections
     * @throws \Exception
     */
    public function setPropertyInjection($propertyInjections)
    {
        if (!is_array($propertyInjections)) {
            throw new \Exception(__METHOD__." only accept parameter as array.");
        }

        foreach ($propertyInjections as $controller => $properties) {

            foreach ($properties as $key => $value) {
                $classInstance = Inflector::instance()->toNamespace($value);
                $this->definitions["\\".ucfirst(APPPATH).$this->namespace.$controller][$key] = new $classInstance;
            }
        }
    }

    /**
     * @param $services
     * @return $this
     * @throws \Exception
     */
    public function setService($services)
    {
        if (!is_array($services)) {
            throw new \Exception(__METHOD__." accept parameter as array.");
        }

        foreach ($services as $key => $alias) {
            $this[$key] = $alias;
        }

        return $this;
    }

    public function factory()
    {
        return $this->factory;
    }

    /**
     * @param $key
     * @return null
     */
    public function getDefinitions($key = null)
    {
        if (!is_null($key)) {
            return isset($this->definitions[$key]) ? $this->definitions[$key] : null ;
        } else {
            return !empty($this->definitions) ? $this->definitions : array();
        }
    }

    /**
     * Inject all your properties into controller at run time
     * @param $controllerInstance
     * @param $controller
     * @throws Exception
     */
    public function propertyInjection($controllerInstance, $controller)
    {
        $definition = $this->getDefinition();

        $injectableDefinitions = $definition()->getPropertyDependencies();

        $this->setPropertyInjection($injectableDefinitions);

        $dependencies = $this->getDefinitions($controller);

        if (array_key_exists($controller, $this->definitions)) {

            $reflection = new Reflection();
            $reflection->setClass($controller);

            foreach ($dependencies as $classProperty => $object) {

                if (!$reflection->reflectionClass->hasProperty($classProperty)) {
                    throw new Exception(
                        sprintf("Property %s is not defined in $controller controller", $classProperty)
                    );
                }

                $reflection->makePropertyAccessible($classProperty);
                //set property value
                $reflection->reflectionProperty->setValue(
                    $controllerInstance, $object
                );
            }
        }
    }
}
