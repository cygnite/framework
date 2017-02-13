<?php
namespace Cygnite\Container;

use Cygnite\Helpers\Inflector;

class Injector
{
    private $definition = [];

    /**
     * @param $dependency
     *
     * @return array
     */
    public function getReflectionParam($dependency)
    {
        $resolveClass = null;
        //Get constructor class name
        $resolveClass = $dependency->getClass()->name;

        return [$resolveClass, new \ReflectionClass($resolveClass)];
    }

    /**
     * @param $dependency
     *
     * @return mixed
     */
    public function isOptionalArgs($dependency)
    {
        /*
         | Check parameters are optional or not
         | if it is optional we will set the default value
         */
        if ($dependency->isOptional()) {
            return $dependency->getDefaultValue();
        }
    }

    /**
     * We will inject interface implementation.
     *
     * @param $reflectionParam
     *
     * @return array
     */
    public function interfaceInjection($reflectionParam)
    {
        $constructorArgs = null;
        /*
         | Check if constructor dependency is Interface or not.
         | if interface we will check definition for the interface
         | and inject into controller constructor
         */
        if (!$reflectionParam->IsInstantiable() && $reflectionParam->isInterface()) {
            $aliases = $this->definition['definition.config']['register.alias'];
            $interface = Inflector::getClassName($reflectionParam->getName());

            if (array_key_exists($interface, $aliases)) {
                $constructorArgs = $this->makeInstance($aliases[$interface]);
            }
        }

        return $constructorArgs;
    }

    /**
     * @param $dependency
     * @param $arguments
     *
     * @return mixed
     */
    public function checkIfConstructorHasDefaultArgs($dependency, $arguments)
    {
        $parameters = $dependency->getDefaultValue();

        if (empty($parameters) && !empty($arguments)) {
            $parameters = $arguments;
        }

        return $parameters;
    }
    
    /**
     * Set definition configuration.
     * 
     * @param array $definition
     */
    public function setDefinitionConfig(array $definition = [])
    {
        $this->definition = ['definition.config' => $definition];
    }

    /**
     * Set Container instance.
     *
     * @param $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}