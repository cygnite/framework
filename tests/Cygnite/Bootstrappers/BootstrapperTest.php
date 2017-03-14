<?php
use Cygnite\Bootstrappers\Paths;
use Cygnite\Bootstrappers\Bootstrapper;
use PHPUnit\Framework\TestCase;

class BootstrapperTest extends TestCase
{
    public function testReturnsDefaultFrameworkBootstrappers()
    {
        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths([]));
        $this->assertEquals([
            \Cygnite\Foundation\Bootstrappers\ApplicationBootstraper::class,
            \Cygnite\Foundation\Bootstrappers\LogBootstraper::class,
        ], $bootstrapper->all());
    }

    public function testAddingAdditionalBootstrappers()
    {
        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths([]));
        $bootstrapper->registerBootstrappers([
            \Cygnite\Tests\Bootstrappers\Executes\AppBootstraper::class
        ]);

        $this->assertEquals([
            \Cygnite\Foundation\Bootstrappers\ApplicationBootstraper::class,
            \Cygnite\Foundation\Bootstrappers\LogBootstraper::class,
            \Cygnite\Tests\Bootstrappers\Executes\AppBootstraper::class
        ], $bootstrapper->all());
    }

    public function testReturnsPathsObject()
    {
        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths([]));
        $this->assertInstanceof(\Cygnite\Bootstrappers\Paths::class, $bootstrapper->getPaths());
    }
}
