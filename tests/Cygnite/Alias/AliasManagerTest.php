<?php
use PHPUnit\Framework\TestCase;

class AliasManagerTest extends TestCase
{
    private $config = [
                'Dependency' => \Cygnite\Tests\Container\ContainerDependency::class,
    ];

    public function testCreateAssetInstance()
    {
        $alias = new \Cygnite\Alias\Manager();
        $this->assertInstanceOf('Cygnite\Alias\Manager', $alias);
    }

    public function testAliasClasses()
    {
        $alias = new \Cygnite\Alias\Manager($this->config);
        $alias->register();
        $this->assertEquals(new \Cygnite\Tests\Container\ContainerDependency, new Dependency());
        $alias->unregister();
    }

    public function testAliasNamespace()
    {
        $alias = new \Cygnite\Alias\Manager();
        $alias->namespace('\Cygnite\Tests\Container', '\Container');
        $alias->register();
        $this->assertEquals(new \Cygnite\Tests\Container\ContainerDependency(), new \Container\ContainerDependency());
        $alias->unregister();
    }

    public function testSetMethod()
    {
        $alias = new \Cygnite\Alias\Manager();
        $alias->set('foo', 'FooBar');
        $this->assertSame('FooBar', $alias->get('foo'));
    }

    public function testGetAndHasMethod()
    {
        $alias = new \Cygnite\Alias\Manager();
        $alias->set('foo', 'FooBar');
        $alias->set('bar', 'BarBaz');
        $this->assertTrue($alias->has('bar'));
        $this->assertFalse($alias->has('baz'));
    }

    public function testRemoveMethod()
    {
        $alias = new \Cygnite\Alias\Manager();
        $alias->set('foo', 'FooBar');
        $alias->set('bar', 'BarBaz');
        $this->assertTrue($alias->has('bar'));
        $alias->remove('bar');
        $this->assertFalse($alias->has('bar'));
    }
}
