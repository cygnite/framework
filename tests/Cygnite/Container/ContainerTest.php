<?php
use Cygnite\Container\Container;
use Mockery as m;

class ContainerTest extends PHPUnit_Framework_TestCase
{
	private $_container;

	public function setUp()
	{
		$this->_container = new Container();
	}

	public function testMakeClass()
	{
        $router = new \Cygnite\Base\Router\Router();
        $url = new \Cygnite\Common\UrlManager\Url($router);

        $madeUrl = $this->_container->make('\Cygnite\Common\UrlManager\Url');

        $this->assertEquals($url, $madeUrl);
	}

	public function testClouserResolutionAsObject()
	{
		$this->_container->name = function() {
			return 'Cygnite';
		};

		$this->assertEquals('Cygnite', $this->_container->name);
	}

	public function testArrayAccess()
	{
		$this->_container['name'] = function() {
			return 'Cygnite';
		};

		$this->assertTrue($this->_container->has('name'));
		$this->assertEquals('Cygnite', $this->_container['name']());
		$this->_container->offsetUnset('name');
        $this->assertFalse(isset($this->_container['name']));
	}

	public function testSingletonClouserResolution()
	{
		$class = new stdClass;
        $this->_container->singleton('social', function () use ($class) { return $class; });
        $this->assertSame($class, $this->_container['social']);
	}

	public function testShareMethod()
	{
        $closure = $this->_container->share(function () { 
        	return new stdClass; 
        });

        $class1 = $closure($this->_container);
        $class2 = $closure($this->_container);

        $this->assertSame($class1, $class2);		
	}

	public function testExtendMethod()
	{
		$this->_container['foo'] = 'foo';
		$this->_container['bar'] = function ($c) {
			return new stdClass($c['foo']);
		};

		$this->_container['foobar'] = $this->_container->extend('bar', function ($bar, $c) {
			$bar->name = 'FooBar';

			return $bar;
		});
		
        $this->assertEquals('FooBar', $this->_container['foobar']()->name);
	}

    public function testExtendWithoutOverrideIntoKey()
    {
        $this->_container->foo = function () { return 'foo'; };

        $this->_container->extend('foo', function ($foo) {
            return $foo.'bar';
        });        

        $this->assertEquals('foobar', $this->_container['foo']());
    }

    public function testMakeInstance()
    {
    	$stdClass = $this->_container->makeInstance('\stdClass');

    	$this->assertEquals(new stdClass, $stdClass);
    }
}
