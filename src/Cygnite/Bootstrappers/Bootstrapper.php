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
    ];


    /**
     * Constructor
     * @param Paths $paths The paths to various folders
     */
    public final function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @param array $classes
     */
    public function registerBootstrappers(array $classes)
    {
        $this->bootstrappers = array_merge($this->bootstrappers, $classes);
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