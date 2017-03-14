<?php
use PHPUnit\Framework\TestCase;

class ApcCacheTest extends TestCase
{
    public function testGetMethodReturnValue()
    {
        $apc = $this->getMockBuilder('Cygnite\Cache\Storage\ApcWrapper')->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->will($this->returnValue('foobar'));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $this->assertEquals('foobar', $cache->get('foobar'));
    }

    public function testGetMethodReturnsNullWhenItemNotFound()
    {
        $apc = $this->getMockBuilder('Cygnite\Cache\Storage\ApcWrapper')->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($this->equalTo('foo'))->will($this->returnValue(null));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $this->assertNull($cache->get('foo'));
    }

    public function testIncrementMethod()
    {
        $apc = $this->getMockBuilder('Cygnite\Cache\Storage\ApcWrapper')->setMethods(['increment'])->getMock();
        $apc->expects($this->once())->method('increment')->with($this->equalTo('bar'), $this->equalTo(10));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->increment('bar', 10);
    }

    public function testDecrementMethod()
    {
        $apc = $this->getMockBuilder('Cygnite\Cache\Storage\ApcWrapper')->setMethods(['decrement'])->getMock();
        $apc->expects($this->once())->method('decrement')->with($this->equalTo('barbaz'), $this->equalTo(3));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->decrement('barbaz', 3);
    }

    public function testDestroyMethod()
    {
        $apc = $this->getMockBuilder('Cygnite\Cache\Storage\ApcWrapper')->setMethods(['destroy'])->getMock();
        $apc->expects($this->once())->method('destroy')->with($this->equalTo('baz'));
        $cache = new Cygnite\Cache\Storage\Apc($apc);
        $cache->destroy('baz');
    }
}
