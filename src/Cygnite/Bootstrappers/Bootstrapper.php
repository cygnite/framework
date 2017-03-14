<?php
namespace Cygnite\Bootstrappers;

use Cygnite\Bootstrappers\Paths;

/**
 * Class Bootstrapper
 * @package Cygnite\Bootstrappers
 */
class Bootstrapper
{
    protected $paths;

    protected $bootstrappers = [
        \Cygnite\Foundation\Bootstrappers\ApplicationBootstraper::class,
        \Cygnite\Foundation\Bootstrappers\LogBootstraper::class,
    ];


    /**
     * Constructor
     * @param Paths $paths The paths to various folders
     */
    final public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @param array $classes
     */
    public function registerBootstrappers(array $classes, $override = false)
    {
        if ($override) {
            $this->bootstrappers = $classes;

            return $this;
        }

        $this->bootstrappers = array_merge($this->bootstrappers, $classes);

        return $this;
    }

    /**
     * Get all paths
     */
    public function getPaths() : Paths
    {
        return $this->paths;
    }

    /**
     * Return all bootstrappers
     * @return mixed
     */
    public function all() : array
    {
        return $this->bootstrappers;
    }
}
