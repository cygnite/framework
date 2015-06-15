<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\DependencyInjection;

use Closure;

/**
 * Describes the basic interface of a container.
 *
 * @since v1.0.8
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
     * @return mixed Entry. Can be anything: object, value, ...
     */
    public function get($name);

    /**
     * Check if the container can return an entry for the given name.
     *
     * @param string $name Name of the entry to look for.
     * @param        $name
     * @return bool
     */
    public function has($name);

    /**
     * @param $class
     * @return mixed
     */
    public function make($class);

    /**
     * @param          $key
     * @param callable $callable
     * @return mixed
     */
    public function extend($key, Closure $callable);
}
