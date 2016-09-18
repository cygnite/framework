<?php
namespace Cygnite\Bootstrappers;

use Cygnite\Bootstrappers\Paths;

class Bootstrapper
{
    protected $paths;

    private $bootstrappers = [];


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
    public function all()
    {
        return $this->bootstrappers;
    }
}