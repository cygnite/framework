<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Alias;

/**
 * Class Manager.
 *
 * @package Cygnite\Alias
 */
class Manager
{
    /**
     * @var array Class aliases
     */
    protected $aliases = [];

    protected $registered = false;

    protected static $instance;

    protected $resolved = [];

    protected $namespaces = [];

    /**
     * Alias Manager constructor.
     *
     * @param array $aliases
     */
    public function __construct(array $aliases = [])
    {
        if (!empty($aliases)) {
            $this->alias($aliases);
        }
    }

    /**
     * Get or create the singleton alias manager instance.
     *
     * @param  array $aliases
     * @return Manager
     */
    public static function getInstance(array $aliases = []) : Manager
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }

        static::$instance->alias($aliases);

        return static::$instance;
    }

    /**
     * Set number of alias of class as array.
     *
     * @param array $class
     * @param string $alias
     * @return Manager
     */
    public function alias(array $class = [], string $alias = null) : Manager
    {
        if (!is_array($class)) {
            $this->set($class, $alias);

            return $this;
        }

        $this->aliases = array_merge($this->get(), $class);

        return $this;
    }

    /**
     * Register a namespace alias.
     *
     * @param $namespace
     * @param $alias
     * @return Manager
     */
    public function namespace(string $namespace, string $alias = null) : Manager
    {
        $this->namespaces[] = [trim($namespace, '\\'), trim($alias, '\\')];

        return $this;
    }

    /**
     * Store aliases into array stack.
     *
     * @param $alias
     * @param $class
     * @return bool
     */
    public function set(string $alias, string $class) : bool
    {
        $this->aliases[$alias] = $class;

        return true;
    }

    /**
     * Returns the value stored in stack.
     *
     * @param string|null $alias
     * @return array|bool|mixed
     */
    public function get(string $alias = null)
    {
        if (is_null($alias)) {
            return $this->aliases;
        }

        return $this->has($alias) ? $this->aliases[$alias] : false;
    }

    /**
     * Check alias existence in the array stack.
     *
     * @param $alias
     * @return bool
     */
    public function has($alias) : bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Remove the stored alias.
     *
     * @param $alias
     * @return bool
     */
    public function remove($alias) : bool
    {
        unset($this->aliases[$alias]);

        return true;
    }

    /**
     * Resolve namespace alias and return class name.
     *
     * @param string $alias
     * @return bool|string
     */
    public function resolveNamespaceAlias(string $alias)
    {
        foreach ($this->namespaces as $namespace) {
            list($className, $aliasTo) = $namespace;

            if (false == strpos($alias, $aliasTo)) {
                if (!empty($aliasTo)) {
                    $alias = substr($alias, strlen($aliasTo) + 1);
                }

                return $this->getClassForNamespaceAlias($className.'\\'.$alias);
            }
        }

        return false;
    }

    /**
     * Returns the class name.
     *
     * @param $class
     * @return bool
     */
    protected function getClassForNamespaceAlias($class)
    {
        $this->resolved[] = $class;
        if (class_exists($class, true)) {
            array_pop($this->resolved);

            return $class;
        }

        return false;
    }

    /**
     * Resolve all aliases.
     *
     * @param string $alias
     * @return bool
     */
    public function resolve(string $alias) : bool
    {
        // return false if alias already resolved.
        if (in_array($alias, $this->resolved)) {
            return false;
        }

        $this->resolved[] = $alias;

        // Resolve class alias if set.
        if ($this->has($alias)) {
            $class = $this->get($alias);
            $class = (class_exists($class, true)) ? $class : false;
        } else {
            // Resolve namespace alias.
            $class = $this->resolveNamespaceAlias($alias);
        }

        // Remove the resolved class
        array_pop($this->resolved);

        // If class exists create and return a alias of the class.
        return (!class_exists($class)) ? false : class_alias($class, $alias);
    }

    /**
     * Register the alias manager.
     *
     * @return Manager
     */
    public function register() : Manager
    {
        spl_autoload_register([$this, 'resolve'], true, true);

        return $this;
    }

    /**
     * Unregister alias manager.
     *
     * @return Manager
     */
    public function unregister() : Manager
    {
        spl_autoload_unregister([$this, 'resolve']);

        return $this;
    }

    /**
     * Clone method.
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
