<?php
namespace Cygnite\Container\Service;

use Cygnite\Container\ContainerAwareInterface;

/**
 * Class Container.
 *
 * @author  Sanjoy Dey
 */
abstract class ServiceProvider
{
    protected $container;

    /**
     * Create a new service provider instance.
     *
     * @param \Cygnite\Container\ContainerAwareInterface $container
     */
    public function __construct(ContainerAwareInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register the service provider.
     *
     * @param \Cygnite\Container\ContainerAwareInterface $container
     *
     * @return void
     */
    abstract public function register(ContainerAwareInterface $container);
}
