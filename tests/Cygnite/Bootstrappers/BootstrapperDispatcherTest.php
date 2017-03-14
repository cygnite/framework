<?php
use Cygnite\Bootstrappers\Paths;
use Cygnite\Bootstrappers\Bootstrapper;
use Cygnite\Bootstrappers\BootstrapperDispatcher;
use Cygnite\Container\Container;
use Cygnite\Tests\Container\ContainerDependency;
use PHPUnit\Framework\TestCase;

class BootstrapperDispatcherTest extends TestCase
{
    public function setUp()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );
    }

    public function testPathInstance()
    {
        $bootDispatcher = $this->getMockBuilder('Cygnite\Bootstrappers\BootstrapperDispatcher')->setConstructorArgs([$this->container, new Paths([])])->getMock();
        $this->assertInstanceOf('Cygnite\Bootstrappers\BootstrapperDispatcher', $bootDispatcher);
    }

    public function testBootstrappersDispatchesCorrectly()
    {
        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths([]));
        $bootstrapper->registerBootstrappers([
            \Cygnite\Tests\Bootstrappers\Executes\AppBootstraper::class
        ], true);

        ob_start();
        (new BootstrapperDispatcher($this->container, $bootstrapper))->execute();

        $this->assertEquals('Application Initialized', ob_get_contents());

        ob_clean();
        // Cleanup
        ob_end_clean();
    }

    public function testBootstrapperDispatcherReturnsBootstrapperObject()
    {
        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths([]));
        $dispatcher = (new BootstrapperDispatcher($this->container, $bootstrapper));
        $this->assertInstanceOf('\Cygnite\Bootstrappers\Bootstrapper', $dispatcher->getBootstrapper());
    }
}
