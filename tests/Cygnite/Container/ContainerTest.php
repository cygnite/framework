<?php
use Cygnite\Container\Container;
use PHPUnit\Framework\TestCase;
use Cygnite\Tests\Container\ContainerDependency;

class ContainerTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );
    }

    // need to create a test class
    public function testMakeClassTest()
    {
        $dependencies = $this->container->make('TestClassDependencies');
        $this->assertEquals(new TestClassDependencies(new TestA), $this->container->make('TestClassDependencies'));
        $this->assertEquals(new TestA, $dependencies->getAInstance());
        $this->assertEquals('Hello A', $dependencies->getAInstance()->getA());
    }

    public function testMethodAutoResolvesDependencies()
    {
        $methodArgs = $this->container->resolveMethod('TestClassDependencies', 'indexAction');
        $this->assertInstanceOf('TestMethodResolve', $methodArgs[0]);
        $this->assertEquals('Hello Container', $methodArgs[0]->greet('Container'));
    }

    public function testInterfaceInjection()
    {
        $dependencies = $this->container->make('TestInterfaceInjection');
        $this->assertInstanceOf('\\TestClassDependenciesImplement', $dependencies->getImplementationObject());
        $this->assertEquals('Container', $dependencies->getImplementationContainer());
    }

    public function testPropertyInjection()
    {
        /*
        $instance = $this->container->make('TestClassDependencies');
        $dependencies = new ContainerDependency();
        $this->container->setPropertyInjection($dependencies->getDefinitiions()['property.definition']);
        $this->container->propertyInjection($instance, "TestClassDependencies");
        show(call_user_func_array([$instance, 'indexAction'], [new TestMethodResolve]));
        show($instance->getApi());exit;
        $this->assertInstanceOf('TestMethodResolve', $instance->getApi());*/
    }

    public function testClouserResolutionAsObject()
    {
        $this->container->name = function () {
            return 'Cygnite';
        };

        $this->assertEquals('Cygnite', $this->container->name);
    }


    public function testArrayAccess()
    {
        $this->container['name'] = function () {
            return 'Cygnite';
        };

        $this->assertTrue($this->container->has('name'));
        $this->assertEquals('Cygnite', $this->container['name']());
        $this->container->offsetUnset('name');
        $this->assertFalse(isset($this->container['name']));
    }

    public function testSingletonClouserResolution()
    {
        $class = new stdClass();
        $this->container->singleton('social', function () use ($class) {
            return $class;
        });
        $this->assertSame($class, $this->container['social']);
    }

    public function testShareMethod()
    {
        $closure = $this->container->share(function () {
            return new stdClass();
        });

        $class1 = $closure($this->container);
        $class2 = $closure($this->container);

        $this->assertSame($class1, $class2);
    }

    public function testExtendMethod()
    {
        $this->container['foo'] = 'foo';
        $this->container['bar'] = function ($c) {
            return new stdClass($c['foo']);
        };

        $this->container['foobar'] = $this->container->extend('bar', function ($bar, $c) {
            $bar->name = 'FooBar';

            return $bar;
        });


        $this->assertEquals('FooBar', $this->container['foobar']()->name);
    }

    public function testExtendWithoutOverrideIntoKey()
    {
        $this->container->foo = function () {
            return 'foo';
        };

        $this->container->extend('foo', function ($foo) {
            return $foo.'bar';
        });

        $this->assertEquals('foobar', $this->container['foo']());
    }

    public function testMakeInstance()
    {
        $stdClass = $this->container->makeInstance('\stdClass');

        $this->assertEquals(new stdClass(), $stdClass);
    }
}

class TestClassDependencies
{
    private $api;

    public function __construct(TestA $a)
    {
        $this->a = $a;
    }

    public function getAInstance()
    {
        return $this->a;
    }

    public function indexAction(TestMethodResolve $methodResolve, $name = 'Container')
    {
        return $methodResolve->greet($name);
    }

    public function getApi()
    {
        return $this->api;
    }
}

interface TestImplementInterface
{
    public function container();
}

class TestClassDependenciesImplement implements TestImplementInterface
{
    public function container()
    {
        return 'Container';
    }
}

class TestInterfaceInjection
{
    private $interface;

    public function __construct(TestImplementInterface $testImplement)
    {
        $this->interface = $testImplement;
    }

    public function getImplementationObject()
    {
        return $this->interface;
    }

    public function getImplementationContainer()
    {
        return $this->interface->container();
    }
}

class TestA
{
    public function getA()
    {
        return 'Hello A';
    }
}

class TestMethodResolve
{
    public function greet($name)
    {
        return "Hello $name";
    }
}
