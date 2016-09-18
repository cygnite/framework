<?php
namespace Cygnite\Bootstrappers;

use Cygnite\Container\ContainerAwareInterface;

/**
 * Class BootstrapperDispatcher.
 *
 * @package Cygnite\Bootstrappers.
 */
class BootstrapperDispatcher implements BootstrapperDispatcherInterface
{
    protected $container;

    protected $bootstrappers = [];

    private $instances = [];

    /**
     * Constructor.
     *
     * @param ContainerAwareInterface $container
     * @param $bootstrappers
     */
    public function __construct(ContainerAwareInterface $container, $bootstrappers)
    {
        $this->container = $container;
        $this->bootstrappers = $bootstrappers;
    }

    /**
     * Get bootstrappers instance
     * @return array
     */
    public function getBootstrapper()
    {
        return $this->bootstrappers;
    }

    /**
     * Run all defined bootstrappers
     *
     * @throws \RuntimeException
     */
    public function execute()
    {
        foreach (array_unique($this->bootstrappers->all()) as $class) {
            $bootstrapper = $this->create($class);

            if (!method_exists($bootstrapper, 'run')) {
                throw new \RuntimeException("$class must have run() method");
            }

            $bootstrapper->run();
        }
    }

    /**
     * Store all bootstrappers into instance stack
     *
     * @param string $class
     */
    public function create(string $class) : BootstrapperInterface
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = new $class($this->container, $this->bootstrappers->getPaths());
        }

        return $this->instances[$class];
    }
}