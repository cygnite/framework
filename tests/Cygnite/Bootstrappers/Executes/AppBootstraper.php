<?php
namespace Cygnite\Tests\Bootstrappers\Executes;

use Cygnite\Bootstrappers\Paths;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;

class AppBootstraper implements BootstrapperInterface
{
    private $container;

    protected $paths;

    public function __construct(ContainerAwareInterface $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;
    }

    public function run()
    {
        echo "Application Initialized";
    }
}
