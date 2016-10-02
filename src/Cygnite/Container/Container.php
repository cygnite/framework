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
use InvalidArgumentException;
use Cygnite\Helpers\Inflector;
use Cygnite\Container\Exceptions\ContainerException;
use Cygnite\Container\Dependency\Builder as DependencyBuilder;

/**
 * Class Container.
 *
 * @author Sanjoy Dey
 */
class Container extends DependencyBuilder implements ContainerAwareInterface, ArrayAccess
{
    /** @var Reflection */
    protected $reflection;

    /** @var Injector */
    protected $injector;

    /** @var array The container's bind data. */
    protected $stack = [];

    /**
     * Constructor to set the container dependencies.
     *
     * @param Injector $injector
     * @param array $definitions
     * @param string $namespace
     * @internal param string $namespace
     */
    public function __construct(
        Injector $injector,
        array $definitions = [],
        string $namespace = null
    ) {
        $this->reflection = new Reflection();
        $this->setInjector($injector);

        if (!empty($definitions)) {
            $this->set('definition.config', $definitions);
            $this->setPropertyDefinition($definitions['property.definition']);
        }

        if (!is_null($namespace)) {
            $this->setAppNamespace($namespace);
        }
    }

    /**
     * Set Injector Instance.
     *
     * @param $injector
     * @return ContainerAwareInterface
     */
    public function setInjector(Injector $injector) : ContainerAwareInterface
    {
        $this->injector = $injector;

        return $this;
    }

    /**
     * Returns injector instance.
     */
    public function getInjector() : Injector
    {
        return $this->injector;
    }

    /**
     * Set definitions for property and interface injection.
     *
     * @param array $definitions
     * @return ContainerAwareInterface
     */
    public function setDefinitions(array $definitions) : ContainerAwareInterface
    {
        $this->set('definition.config', $definitions);
        parent::setPropertyDefinition($definitions['property.definition']);

        return $this;
    }

    /**
     * Set application namespace.
     *
     * @param $namespace
     * @return $this
     */
    public function setApplicationNamespace($namespace)
    {
        return parent::setAppNamespace($namespace);
    }

    /**
     * Returns Reflection instance.
     *
     * @return Reflection
     */
    public function getReflection() : Reflection
    {
        return $this->reflection;
    }

    /**
     * Set value to container.
     *
     * @param $key
     * @param $instance
     * @return ContainerAwareInterface
     */
    public function set($key, $instance) : ContainerAwareInterface
    {
        $this[$key] = $instance;

        return $this;
    }

    /**
     * Returns value if stored in container.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * Check if value exists in container.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
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
     * Returns all stored items from container's stack
     *
     * @return array
     */
    public function all()
    {
        return $this->stack;
    }

    /**
     * Assigns a value to the specified data.
     *
     * @param string The data key to assign the value to.
     * @param mixed  The value to set.
     */
    public function __set($key, $value)
    {
        $this->stack[$key] = $value;
    }

    /**
     * Get a data by key.
     *
     * @param $key
     * @return mixed
     */
    public function &__get($key)
    {
        if (!isset($this->stack[$key])) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $key));
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
     * Whether or not an data exists by key.
     *
     * @param string An data key to check for.
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->stack[$key]);
    }

    /**
     * Unset an data by key.
     *
     * @param string The key to unset.
     */
    public function __unset($key)
    {
        unset($this->stack[$key]);
    }

    /**
     * Adds an object to the shared pool.
     *
     * @param mixed $key
     * @return bool
     */
    public function isShared($key)
    {
        return array_key_exists($key, $this->stack);
    }

    /**
     * Removes an object from the shared pool.
     *
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
     * Assigns a value to the specified offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->stack[] = $value;
        }

        $this->stack[$offset] = $value;
    }

    /**
     * Returns the value at specified offset.
     *
     * @param mixed|object $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->stack[$offset] : null;
    }

    /**
     * Whether or not an offset exists.
     *
     * @param mixed|object $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->stack[$offset]);
    }

    /**
     * Unset an offset.
     *
     * @param mixed $offset
     *
     * @internal param $string offset to unset
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            $this->__unset($offset);
        }
    }

    /**
     * Reference
     * http://fabien.potencier.org/article/17/on-php-5-3-lambda-functions-and-closures.
     *
     * @param Closure $callable
     * @internal param $callable
     * @return Closure
     */
    public function share(Closure $callable) : Closure
    {
        return function () use ($callable) {
            static $object;
            $c = $this;

            if (is_null($object) && $callable instanceof Closure) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    /**
     * Extends the existing object.
     *
     * @param string $key
     * @param callable|Closure $callable $callable
     * @return callable
     */
    public function extend(string $key, Closure $callable)
    {
        if (!isset($this->stack[$key])) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $key));
        }

        if (!$callable instanceof Closure) {
            throw new InvalidArgumentException(
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
     * Get singleton instance of your class.
     *
     * @param      $name
     * @param null $callback
     * @return mixed
     */
    public function singleton(string $name, callable $callback = null)
    {
        static $instance = [];

        // if closure callback given we will create a singleton instance of class
        // and return it to user
        if ($callback instanceof Closure) {
            if (!isset($instance[$name])) {
                $instance[$name] = $callback($this);
            }

            return $this->stack[$name] = $instance[$name];
        }

        /*
         | If callback is not instance of closure then we will simply
         | create a singleton instance and return it
         */
        if (!isset($instance[$name])) {
            $instance[$name] = new $name();
        }

        return $instance[$name];
    }

    /**
     * Resolve the class. We will create and return instance if already
     * not exists.
     *
     * @param       $class
     * @param array $arguments
     * @return object
     */
    public function resolve($class, $arguments = [])
    {
        $class = Inflector::toNamespace($class);
        return $this->make($class, $arguments);
    }

    /**
     * Resolve all dependencies of your class and return instance of
     * your class.
     *
     * @param $class
     * @throws \Cygnite\Container\Exceptions\ContainerException
     * @return mixed
     */
    public function make(string $namespace, array $arguments = [])
    {
        $class = $this->getClassNameFromNamespace($namespace);
        /*
         * If instance of the class already created and stored into
         * stack then simply return from here
         */
        if ($this->has($class)) {
            return $this->get($class);
        }
        $reflectionClass = $this->reflection->setClass($namespace)->getReflectionClass();
        $this->throwExceptionIfNotInstantiable($namespace, $reflectionClass);

        $constructor = null;
        $constructorArgsCount = 0;
        list($constructor, $constructorArgsCount) = $this->getConstructorArgs($reflectionClass, $constructorArgsCount);

        // if class does not have explicitly defined constructor or constructor
        // does not have parameters get the new instance
        if (!isset($constructor) && is_null($constructor) || $constructorArgsCount < 1) {
            return $this[$class] = $reflectionClass->newInstance();
        }

        $dependencies = $constructor->getParameters();
        $constructorArgs = $this->createMethodArgument($dependencies, $arguments);

        return $this[$class] = $reflectionClass->newInstanceArgs($constructorArgs);
    }

    /**
     * This method is used to find out the arguments required for
     * the constructor or any method and returns array of arguments.
     *
     * @param array $dependencies
     * @param array $arguments
     * @return array
     */
    protected function createMethodArgument(array $dependencies, array $arguments = []) : array
    {
        $args = [];
        foreach ($dependencies as $dependency) {
            if (!is_null($dependency->getClass())) {
                $args[] = $this->resolverClass($dependency, $arguments);
            } else {
                // Check if construct has default value then we will simply assign it into array
                // and continue for next argument
                if ($dependency->isDefaultValueAvailable()) {
                    $args[] = $this->injector->checkIfConstructorHasDefaultArgs($dependency, $arguments);
                    continue;
                }
                //Check if parameters are optional then we will set the default value
                $args[] = $this->injector->isOptionalArgs($dependency);
            }
        }

        return $args;
    }

    /**
     * This method is used to resolve all dependencies of
     * your method and returns method arguments.
     *
     * @param string $namespace
     * @param string $method
     * @return array
     */
    public function resolveMethod(string $namespace, string $method) : array
    {
        $class = $this->reflection->setClass($namespace)->getReflectionClass();
        $arguments = $class->getMethod($method)->getParameters();
        $methodArgs = $this->createMethodArgument($arguments);

        return array_filter($methodArgs);
    }

    /**
     * Returns class name from the namespace.
     * @return string
     */
    protected function getClassNameFromNamespace($namespace) : string
    {
        $namespaceArr = explode('\\', $namespace);

        return strtolower(end($namespaceArr));
    }

    /**
     * Get Class Constructor Arguments.
     *
     * @param $reflectionClass
     * @param int $constructorArgsCount
     * @return array
     */
    private function getConstructorArgs($reflectionClass, int $constructorArgsCount = 0)
    {
        if ($reflectionClass->hasMethod('__construct')) {
            $constructor = $reflectionClass->getConstructor();
            $constructor->setAccessible(true);
            $constructorArgsCount = $constructor->getNumberOfParameters();

            return [$constructor, $constructorArgsCount];
        }
    }

    /**
     * Throws exception if given input is not instantiable.
     *
     * @param $class
     * @param $reflectionClass
     * @throws Exceptions\ContainerException
     */
    private function throwExceptionIfNotInstantiable($class, $reflectionClass)
    {
        /*
         * Check if reflection class is not instantiable then throw ContainerException
         */
        if (!$reflectionClass->isInstantiable()) {
            $type = ($this->reflection->getReflectionClass()->isInterface() ? 'interface' : 'class');
            throw new ContainerException("Cannot instantiate $type $class");
        }
    }

    /**
     * Resolves class and returns object if instantiable,
     * otherwise checks for interface injection can be done.
     *
     * @param $dependency
     * @param $arguments
     * @return array|mixed
     */
    private function resolverClass($dependency, $arguments)
    {
        list($resolveClass, $reflectionParam) = $this->injector->getReflectionParam($dependency);

        // Application and Container cannot be injected into controller currently
        // since Application constructor is protected
        if ($reflectionParam->IsInstantiable()) {
            return $this->makeInstance($resolveClass, $arguments);
        }

        return $this->injector->interfaceInjection($reflectionParam);
    }

    /**
     * Create new instance.
     *
     * @param       $namespace
     * @param array $arguments
     *
     * @throws Exceptions\ContainerException
     *
     * @return mixed
     */
    public function makeInstance(string $namespace, $arguments = [])
    {
        if (!class_exists($namespace)) {
            throw new ContainerException(sprintf('Class "%s" not exists.', $namespace));
        }
        $class = $this->getClassNameFromNamespace($namespace);

        if ($this->has($class)) {
            return $this->get($class);
        }

        return $this[$class] = new $namespace($arguments);
    }

    /**
     * @param $namespace
     */
    public function createProxy($namespace)
    {

    }
}
