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

/**
 * Describes the basic interface of a container.
 *
 * Interface ContainerAwareInterface
 *
 * @author Sanjoy  Dey
 */
interface ContainerAwareInterface
{
    /**
     * Returns an entry of the container by its name.
     *
     * @param string $name Name of the entry to look for.
     *
     * @throws NotFoundException No entry was found for this name.
     *
     * @return mixed Entry. Can be anything: object, value, ...
     */
    public function get($name);

    /**
     * Check if the container can return an entry for the given name.
     *
     * @param string $name Name of the entry to look for.
     * @param        $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Resolve all dependencies of your class and return instance of
     * your class.
     *
     * @param string $class
     * @param array $arguments
     * @internal param $ |string $class
     *
     * @return mixed
     */
    public function make(string $class, array $arguments = []);

    /**
     * Resolve the class. We will create and return instance if already
     * not exists.
     *
     * @param       $class
     * @param array $arguments
     *
     * @return object
     */
    public function resolve($class, $arguments = []);

    /**
     * Get singleton instance of your class.
     *
     * @param      $key
     * @param null $callback
     *
     * @return mixed
     */
    public function singleton(string $name, callable $callback = null);

    /**
     * Reference
     * http://fabien.potencier.org/article/17/on-php-5-3-lambda-functions-and-closures.
     *
     * @param Closure $callable
     *
     * @internal param $callable
     *
     * @return type
     */
    public function share(\Closure $callable);

    /**
     * @param string   $key
     * @param callable $callable
     *
     * @return mixed
     */
    public function extend(string $key, \Closure $callable);

    /**
     * Create new instance.
     *
     * @param       $class
     * @param array $arguments
     * @throws Exceptions\ContainerException
     * @return mixed
     */
    public function makeInstance(string $class, $arguments = []);
}
