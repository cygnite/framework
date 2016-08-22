<?php


class ApcCacheTest extends PHPUnit_Framework_TestCase
{
    public function testGetMethodReturnValue()
    {
        $apc = $this->getMock('Cygnite\Cache\Storage\ApcWrapper', ['get']);
        $apc->expects($this->once())->method('get')->will($this->returnValue('foobar'));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $this->assertEquals('foobar', $cache->get('foobar'));
    }

    public function testGetMethodReturnsNullWhenItemNotFound()
    {
        $apc = $this->getMock('Cygnite\Cache\Storage\ApcWrapper', ['get']);
        $apc->expects($this->once())->method('get')->with($this->equalTo('foo'))->will($this->returnValue(null));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $this->assertNull($cache->get('foo'));
    }

    public function testIncrementMethod()
    {
        $apc = $this->getMock('Cygnite\Cache\Storage\ApcWrapper', ['increment']);
        $apc->expects($this->once())->method('increment')->with($this->equalTo('bar'), $this->equalTo(10));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->increment('bar', 10);
    }

    public function testDecrementMethod()
    {
        $apc = $this->getMock('Cygnite\Cache\Storage\ApcWrapper', ['decrement']);
        $apc->expects($this->once())->method('decrement')->with($this->equalTo('barbaz'), $this->equalTo(3));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->decrement('barbaz', 3);
    }

    public function testDestroyMethod()
    {
        $apc = $this->getMock('Cygnite\Cache\Storage\ApcWrapper', ['destroy']);
        $apc->expects($this->once())->method('destroy')->with($this->equalTo('baz'));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->destroy('baz');
    }
}
