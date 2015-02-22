<?php
namespace Cygnite\DependencyInjection;

use ArrayAccess;
use Closure;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Reflection;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Class Container
 *
 * @package Cygnite\DependencyInjection
 * @author  Sanjoy Dey
 */

class Container extends DependencyExtension implements ContainerInterface, ArrayAccess
{

    /**
     * The container's bind data
     *
     * @var array
     * @access private
     */
    private $storage = array();

    private $services;


    /**
     * Get a data by key
     *
     * @param $key
     * @throws InvalidArgumentException
     * @return
     * @internal param \Cygnite\Container\The $string key data to retrieve
     * @access   public
     */
    public function &__get($key)
    {
        if (!isset($this->storage[$key])) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $key));
        }

        $set = isset($this->storage[$key]);

        //return $this->storage[$key];
        $return = $set &&
        is_callable($this->storage[$key]) ?
            $this->storage[$key]($this) :
            $this->storage[$key];

        return $return;
    }

    /**
     * Assigns a value to the specified data
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     * @access public
     */
    public function __set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * Reference
     * http://fabien.potencier.org/article/17/on-php-5-3-lambda-functions-and-closures
     *
     * @param Closure $callable
     * @internal param $callable
     * @return type
     */
    public function share(Closure $callable)
    {
        return function ($c) use ($callable) {
            static $object;

            if (is_null($object)) {

                if ($callable instanceof Closure) {
                    $object = $callable($c);
                }
            }

            return $object;
        };
    }

    /**
     * Adds an object to the shared pool
     *
     * @access public
     * @param mixed $key
     * @return bool
     */
    public function isShared($key)
    {
        return array_key_exists($key, $this->storage);
    }

    /**
     * Removes an object from the shared pool
     *
     * @access public
     * @param mixed $class
     * @return void
     */
    public function unShare($class)
    {
        if (array_key_exists($class, $this->storage)) {
            unset($this->storage[$class]);
        }
    }

    /**
     * Whether or not an data exists by key
     *
     * @param string An data key to check for
     * @access public
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Unset an data by key
     *
     * @param string The key to unset
     * @access public
     */
    public function __unset($key)
    {
        unset($this->storage[$key]);
    }

    /**
     * Assigns a value to the specified offset
     *
     * @param mixed $offset
     * @param mixed $value
     * @access   public
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->storage[] = $value;
        } else {
            $this->storage[$offset] = $value;
        }
        //$boundClosure = $value->bindTo($value);
        //$boundClosure();
    }

    /**
     * Whether or not an offset exists
     *
     * @param mixed $offset
     * @internal param $string offset to check for
     * @access   public
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->storage[$offset]);
    }

    /**
     * Unset an offset
     *
     * @param mixed $offset
     * @internal param $string offset to unset
     * @access   public
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->__unset[$offset]);
        }
    }

    /**
     * Returns the value at specified offset
     *
     * @param mixed $offset
     * @internal param \Cygnite\Container\The $string offset to retrieve
     * @access   public
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->storage[$offset] : null;
    }

    public function extend($key, Closure $callable)
    {
        if (!isset($this->storage[$key])) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $key));
        }

        if (!$callable instanceof Closure) {
            throw new InvalidArgumentException(
                sprintf('Identifier "%s" is not Closure Object.', $callable)
            );
        }

        $binding = $this->offsetExists($key) ? $this->storage[$key] : null;

        $extended = function ($container) use ($callable, $binding) {

            if (!is_object($binding)) {
                throw new DependencyException(sprintf('"%s" must be Closure object.', $binding));
            }

            return $callable($binding($container), $container);

        };

        return $this[$key] = $extended;
    }

    /**
     * Returns all defined value names.
     *
     * @return array An array of value names
     */
    public function keys()
    {
        return array_keys($this->storage);
    }

    /**
     * @param $key
     * @param $instance
     */
    public function set($key, $instance)
    {
        $this[$key] = $instance;
    }

    public function getRegisteredInstance()
    {
        return $this->storage;
    }

    /**
     * Get singleton instance of your class
     *
     *
     */
    public function singleton($class)
    {
        static $instance = [];

        if (!isset($instance[$class])) {
            $instance[$class] = new $class;
        }

        return $instance[$class];
    }

    /**
     * Resolve the class. We will create and return instance if already
     * not exists.
     *
     * @param $class
     * @return object
     */
    public function resolve($class)
    {
        $class = Inflector::instance()->toNamespace($class);

        return $this->make($class);
    }

    /**
     * Resolve all dependencies of your class and return instance of
     * your class
     *
     * @param $class string
     * @throws \Exception
     * @return object
     */
    public function make($class)
    {
        $reflection = new Reflection();
        $reflection->setClass($class);

        if (false === $reflection->reflectionClass->isInstantiable()) {
            throw new DependencyException(
                "Cannot instantiate " .
                ($reflection->reflectionClass->isInterface() ? 'interface' : 'class') . " '$class'"
            );
        }

        $constructor = null;
        $constructorArgsCount = '';
        if ($reflection->reflectionClass->hasMethod('__construct')) {

            $constructor = $reflection->reflectionClass->getConstructor();
            $constructorArgsCount = $constructor->getNumberOfParameters();
            $constructor->setAccessible(true);
        }

        // if class does not have explicitly defined constructor or constructor does not have parameters
        // get the new instance
        if (!isset($constructor) && is_null($constructor) || $constructorArgsCount < 1) {
            $this->storage[$class] = $reflection->reflectionClass->newInstance();
        } else {
            $dependencies = $constructor->getParameters();

            foreach ($dependencies as $dependency) {

                if (!is_null($dependency->getClass())) {

                    //Get constructor class name
                    $resolveClass = $dependency->getClass()->name;
                    $reflectionParam = new ReflectionClass($resolveClass);

                    // Application and Container cannot be injected into controller currently
                    // since Application constructor is protected

                    if ($reflectionParam->IsInstantiable()) {
                        $constructorArgs[] = $this->makeInstance($resolveClass);
                    }

                    /*
                     | Check if constructor dependency is Interface or not.
                     | if interface we will check definition for the interface
                     | and inject into controller constructor
                     */
                    if (!$reflectionParam->IsInstantiable() && $reflectionParam->isInterface()) {

                        $definition = $this->getDefinition();
                        $aliases = $definition()->registerAlias();

                        $interface = Inflector::instance()->getClassName($reflectionParam->getName());
                        if (array_key_exists($interface, $aliases)) {
                            $constructorArgs[] = $this->makeInstance($aliases[$interface]);
                        }
                    }
                } else {
                    /*
                     | Check parameters are optional or not
                     | if it is optional we will set the default value
                     */
                    if ($dependency->isOptional()) {
                        $constructorArgs[] = $dependency->getDefaultValue();
                    }
                }
            }

            $this->storage[$class] = $reflection->reflectionClass->newInstanceArgs($constructorArgs);
        }

        return $this->storage[$class];
    }

    /**
     * @param $resolvedClass
     * @return mixed
     * @throws DependencyException
     */
    private function makeInstance($resolvedClass)
    {
        if (!class_exists($resolvedClass)) {
            throw new DependencyException(sprintf('Class "%s" not exists.', $resolvedClass));
        }

        return new $resolvedClass;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }
}
