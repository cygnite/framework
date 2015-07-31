<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Container;

use Closure;
use ArrayAccess;
use Cygnite\Reflection;
use Cygnite\Helpers\Inflector;
use Cygnite\Container\Dependency\Builder as DependencyBuilder;
use Cygnite\Container\Dependency\DependencyInjectorTrait;
use Cygnite\Container\Exceptions\ContainerException;

/**
 * Class Container
 *
 * @package Cygnite\Container
 * @author  Sanjoy Dey
 */
class Container extends DependencyBuilder implements ContainerAwareInterface, ArrayAccess
{
    use DependencyInjectorTrait;
    
    /**
     * The container's bind data
     *
     * @var array
     * @access private
     */
    private $stack = [];

    /**
     * Get a data by key
     *
     * @param $key
     * @throws \InvalidArgumentException
     * @return
     * @internal param \Cygnite\Container\The $string key data to retrieve
     * @access   public
     */
    public function &__get($key)
    {
        if (!isset($this->stack[$key])) {
            throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $key));
        }

        $set = isset($this->stack[$key]);

        //return $this->stack[$key];
        $return = $set &&
        is_callable($this->stack[$key]) ?
            $this->stack[$key]($this) :
            $this->stack[$key];

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
        $this->stack[$key] = $value;
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
        return function () use ($callable) {
            static $object;
            $c = $this;
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
        return array_key_exists($key, $this->stack);
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
        if (array_key_exists($class, $this->stack)) {
            $this->__unset($class);
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
        return isset($this->stack[$key]);
    }

    /**
     * Unset an data by key
     *
     * @param string The key to unset
     * @access public
     */
    public function __unset($key)
    {
        unset($this->stack[$key]);
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
            $this->stack[] = $value;
        } else {
            $this->stack[$offset] = $value;
        }
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
        return isset($this->stack[$offset]);
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
            $this->__unset($offset);
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
        return $this->offsetExists($offset) ? $this->stack[$offset] : null;
    }

    /**
     * @param          $key
     * @param callable $callable
     * @return callable
     * @throws \InvalidArgumentException
     */
    public function extend($key, Closure $callable)
    {
        if (!isset($this->stack[$key])) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $key));
        }

        if (!$callable instanceof Closure) {
            throw new \InvalidArgumentException(
                sprintf('Identifier "%s" is not Closure Object.', $callable)
            );
        }

        $binding = $this->offsetExists($key) ? $this->stack[$key] : null;

        $extended = function () use ($callable, $binding) {

            if (!is_object($binding)) {
                throw new ContainerException(sprintf('"%s" must be Closure object.', $binding));
            }

            return $callable($binding($this), $this);

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
        return array_keys($this->stack);
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
        return $this->stack;
    }

    /**
     * Get singleton instance of your class
     *
     * @param      $key
     * @param null $callback
     * @return mixed
     */
    public function singleton($key, $callback = null)
    {
        static $instance = [];

        // if closure callback given we will create a singleton instance of class
        // and return it to user
        if ($callback instanceof Closure) {
            if (!isset($instance[$key])) {
                $instance[$key] = $callback($this);
            }

            return $this->stack[$key] = $instance[$key];
        }

        /*
         | If callback is not instance of closure then we will simply
         | create a singleton instance and return it
         */
        if (!isset($instance[$key])) {
            $instance[$key] = new $callback();
        }

        return $instance[$key];
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
        $class = Inflector::toNamespace($class);

        return $this->make($class);
    }

    /**
     * Resolve all dependencies of your class and return instance of
     * your class
     *
     * @param $class
     * @return mixed
     * @throws \Cygnite\Container\Exceptions\ContainerException
     */
    public function make($class)
    {
        $reflectionClass = $reflection = null;
        $reflection = new Reflection();
        $reflection->setClass($class);
        $reflectionClass = $reflection->getReflectionClass();

        if (false === $reflectionClass->isInstantiable()) {
            throw new ContainerException(
                "Cannot instantiate " .
                ($reflection->reflectionClass->isInterface() ? 'interface' : 'class') . " '$class'"
            );
        }

        $constructor = null;
        $constructorArgsCount = '';
        if ($reflectionClass->hasMethod('__construct')) {
            $constructor = $reflectionClass->getConstructor();
            $constructorArgsCount = $constructor->getNumberOfParameters();
            $constructor->setAccessible(true);
        }

        // if class does not have explicitly defined constructor or constructor does not have parameters
        // get the new instance
        if (!isset($constructor) && is_null($constructor) || $constructorArgsCount < 1) {
            $this->stack[$class] = $reflectionClass->newInstance();
        } else {
            $dependencies = $constructor->getParameters();
            $constructorArgs = [];

            foreach ($dependencies as $dependency) {
                if (!is_null($dependency->getClass())) {
                    list($resolveClass, $reflectionParam) = $this->getReflectionParam($dependency);

                    // Application and Container cannot be injected into controller currently
                    // since Application constructor is protected
                    if ($reflectionParam->IsInstantiable()) {
                        $constructorArgs[] = $this->makeInstance($resolveClass);
                    } else {
                        $constructorArgs[] = $this->interfaceInjection($reflectionParam);
                    }
                } else {
                    /*
                     | We will check if construct has default value
                     | if exists we will simply assign it and continue
                     | for next argument
                     */
                    if ($dependency->isDefaultValueAvailable()) {
                        $constructorArgs[] = $dependency->getDefaultValue();
                        continue;
                    }
                    /*
                     | Check parameters are optional or not
                     | if it is optional we will set the default value
                     */
                    $constructorArgs[] = $this->isOptionalArgs($dependency);
                }
            }

            $this->stack[$class] = $reflectionClass->newInstanceArgs($constructorArgs);
        }

        return $this->stack[$class];
    }

    /**
     * @param $resolvedClass
     * @return mixed
     * @throws Exceptions\ContainerException
     */
    public function makeInstance($resolvedClass)
    {
        if (!class_exists($resolvedClass)) {
            throw new ContainerException(sprintf('Class "%s" not exists.', $resolvedClass));
        }

        return $this->stack[$resolvedClass] = new $resolvedClass;
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
