<?php
namespace Cygnite\Base\Container;

use ArrayAccess;
use InvalidArgumentException;
use Exception;

class Container implements ArrayAccess
{

    /**
     * The container's bind data
     *
     * @var array
     * @access private
     */
    private $storage = array();

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
    public function __set($key,$value)
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
    public function asShared(Closure $callable)
    {
        return function ($c) use ($callable)
        {
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
     * @return void
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
        if(array_key_exists($class, $this->storage))
        {
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
    public function __isset ($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Unsets an data by key
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
    public function offsetSet($offset,$value)
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
    public function offsetExists($offset) {
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
        
        //$factory = $this->storage[$key];
        
        $extended = function ($container) use ($callable, $binding) {

            if (!is_object($binding)) {
                throw new Exception(sprintf('"%s" must be Closure object.', $binding));
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

    public function set($key, $instance)
    {
        $this[$key] = $instance;
    }

    public function getRegisteredInstance()
    {
        return $this->storage;
    }
    
    public function make($class)
    {
        $ref = new ReflectionClass($class);

        $constructor = $ref->getConstructor() ;
        $constructor->setAccessible( true ) ;
        //$constructor = $ref->getMethod( "__construct" );
        //var_dump($constructor->isInternal());


        // class does not have explicitly defined constructor or constructor does not have parameters
        if (is_null($constructor) || $constructor->getNumberOfParameters() < 1) {
             $instance = $classReflection->newInstance();
        } else {
             foreach ($constructor->getParameters() as $param) {

                    echo "<pre>";
                      var_dump($param->isOptional());
                      //var_dump($param->isOptional());
                    echo "</pre>";

                    if (!is_null($param->getClass())) {
                         $resolveClass = $param->getClass()->getName();
                         $constructorArgs[] = new $resolveClass;
                    } else {
                        if ($param->isOptional()) {
                         $constructorArgs[] = $param->getDefaultValue();	
                        }


                    }

            }

             echo "<pre>";
             print_r($constructorArgs);
             echo "</pre>";	
             $instance = $ref->newInstanceArgs($constructorArgs);
        }
        
        return $instance;
    }
}
