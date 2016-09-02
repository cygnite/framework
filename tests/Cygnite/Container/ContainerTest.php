<?php

use Cygnite\Container\Container;
use Cygnite\Foundation\Application;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    private $container;

    protected $app;

    public function setUp()
    {
        $this->container = new Container();
    }

    // need to create a test class
    public function testMakeClass()
    {
        $this->app = Application::instance();
        $this->app['url'] = new \Cygnite\Common\UrlManager\Url();
        $this->app['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->app['router'] = new \Cygnite\Base\Router\Router($this->app['request']);
        $this->app['router']->setApplication($this->app);
        $this->app['url']->setApplication($this->app);

        $madeUrl = $this->container->make('\Cygnite\Common\UrlManager\Url');
        $madeUrl->setApplication($this->app);

        $this->assertEquals($this->app['url'], $madeUrl);
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
