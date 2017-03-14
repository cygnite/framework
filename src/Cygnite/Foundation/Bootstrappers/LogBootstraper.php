<?php
namespace Cygnite\Foundation\Bootstrappers;

use Cygnite\Bootstrappers\Paths;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;

/**
 * Class LogBootstraper.
 * @package Cygnite\Foundation\Bootstrappers
 */
class LogBootstraper implements BootstrapperInterface
{
    private $container;

    protected $paths;

    public function __construct(ContainerAwareInterface $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;
    }

    /**
     * Register Log instance into Container.
     */
    public function run()
    {
        $this->container->make(\Cygnite\Logger\Log::class);
    }
}
