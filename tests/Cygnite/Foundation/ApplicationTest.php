<?php
use Cygnite\Container\Container;
use Cygnite\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Cygnite\Tests\Container\ContainerDependency;
use Cygnite\Bootstrappers\Bootstrapper;
use Cygnite\Bootstrappers\BootstrapperDispatcher;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;

class ApplicationTest extends TestCase
{
    protected $app;

    public function setUp()
    {
        $paths = [];
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );

        $bootstrapper = new Bootstrapper(new \Cygnite\Bootstrappers\Paths($paths));
        $bootstrapper->registerBootstrappers([\FooBootstrapper::class]);
        $this->app = new Application($this->container, new BootstrapperDispatcher($this->container, $bootstrapper));
    }

    public function testApplicationInstance()
    {
        $this->assertInstanceOf('Cygnite\Foundation\Application', $this->app);
    }

    public function testApplicationReturnsContainerInstance()
    {
        $this->assertSame($this->container, $this->app->getContainer());
    }

    public function testSetValueToContainer()
    {
        $this->app->getContainer()->set('greet', 'Hello Application');

        $this->assertEquals($this->app->getContainer()['greet'], 'Hello Application');
    }

    public function testServiceCreation()
    {
        $this->app->registerServiceProvider(['FooBarServiceProvider']);
        $this->app->setServiceController('bar.controller', '\BarController');

        $container = $this->app->getContainer();
        $this->assertInstanceOf('\FooBar', $container['foo.bar']());
        $this->assertNotNull($container['foo.bar']()->greet());
        $this->assertEquals('Hello FooBar!', $container['foo.bar']()->greet());
        $container['greet.bar.controller'] = 'Hello BarController!';

        $this->assertEquals('Hello BarController!', $container['bar.controller']()->indexAction());
    }

    public function testComposeMethod()
    {
        $bazBar = $this->app->compose('\BazBar', ['greet' => 'Hello!']);

        $this->assertArrayHasKey('greet', $this->app->getContainer()->get('bazbar')->getArguments());
        $this->assertEquals('Hello!', $bazBar->greet());
    }

    public function testServiceController()
    {
        $this->app->registerServiceProvider(['FooBarServiceProvider']);
        $this->app->setServiceController('bar.controller', '\BarController');
        $container = $this->app->getContainer();
        $container['bar.controller']()->getServiceController();

        $this->assertInstanceOf('Cygnite\Mvc\Controller\ServiceController', $container['bar.controller']()->getServiceController());
    }
}

class FooBarServiceProvider
{
    protected $container;

    public function register(ContainerAwareInterface $container)
    {
        $container['foo.bar'] = $container->share(function ($c) {
            return new FooBar();
        });
    }
}

class FooBar
{
    public function greet()
    {
        return 'Hello FooBar!';
    }
}

class BarController
{
    private $container;

    private $serviceController;

    public function __construct($serviceController, \Cygnite\Container\Container $container)
    {
        $this->serviceController = $serviceController;
        $this->container = $container;
    }

    public function indexAction()
    {
        return $this->container['greet.bar.controller'];
    }

    public function getServiceController()
    {
        return $this->serviceController;
    }
}

class BazBar
{
    private $arguments = [];

    public function __construct($arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function greet()
    {
        return $this->arguments['greet'];
    }
}

class FooBootstrapper implements BootstrapperInterface
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

    }
}

