<?php
namespace Cygnite\DependencyInjection;

use SplObjectStorage;

class DependencyExtension extends SplObjectStorage
{
    public $definitions = array();

    public $factory = array();

    public $controller = false;

    public $namespace = '\\Apps\\Controllers\\';

    /**
     * Set all definitions into array
     * @param $propertyInjections
     * @throws \Exception
     */
    public function setPropertyInjection($propertyInjections)
    {
        if (!is_array($propertyInjections)) {
            throw new \Exception(__METHOD__." accept parameter as array.");
        }

        foreach ($propertyInjections as $controller => $property) {

           $this->definitions[$this->namespace.$controller][key($property)] = $property[key($property)];
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
    public function getDefinitions($key)
    {
        return isset($this->definitions[$key]) ? $this->definitions[$key] : null ;
    }

}
