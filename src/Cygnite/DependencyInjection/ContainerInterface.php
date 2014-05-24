<?php

namespace Cygnite\DependencyInjection;

use Closure;

/**
 * Describes the basic interface of a container.
 *
 * @since v1.0.8
 * @author Sanjoy  Dey
 */
interface ContainerInterface
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
     * Tests if the container can return an entry for the given name.
     *
     * @param string $name Name of the entry to look for.
     * @return bool
     */
    public function has($name);

    public function make($class);

    public function extend($key, Closure $callable);
}
