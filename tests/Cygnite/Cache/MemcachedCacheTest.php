<?php
use PHPUnit\Framework\TestCase;

class MemcachedCacheTest extends TestCase
{
    public function testStoreData()
    {
        $memcacheObject = $this->getMockBuilder('StdClass')->setMethods(['set', 'get', 'getResultCode'])->getMock();

        $memcacheObject->expects($this->once())
            ->method('set')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('Foo Bar'));

        $memcacheObject->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('Foo Bar'));

        $memcacheObject->expects($this->once())
            ->method('getResultCode')
            ->will($this->returnValue(0));

        $m = new \Cygnite\Cache\Storage\Memcached($memcacheObject);
        $m->store('foo', 'Foo Bar');
        $this->assertEquals('Foo Bar', $m->get('foo'));
    }

    public function testReturnNullIfDataNotFound()
    {
        $memcacheObject = $this->getMockBuilder('StdClass')->setMethods(['get', 'getResultCode'])->getMock();

        $memcacheObject->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('bar'))
                 ->will($this->returnValue(null));

        $memcacheObject->expects($this->once())
                 ->method('getResultCode')
                 ->will($this->returnValue(1));

        $memcached = new \Cygnite\Cache\Storage\Memcached($memcacheObject);
        $this->assertNull($memcached->get('bar'));
    }

    public function testIncrementMethod()
    {
        $memcache = $this->getMockBuilder('Memcached')->setMethods(['increment'])->getMock();
        $memcache->expects($this->once())
                 ->method('increment')
                 ->with($this->equalTo('foo'), $this->equalTo(3));

        $memcached = new Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->increment('foo', 3);
    }

    public function testDecrementMethod()
    {
        $memcache = $this->getMockBuilder('Memcached')->setMethods(['decrement'])->getMock();
        $memcache->expects($this->once())
                 ->method('decrement')
                 ->with($this->equalTo('foo'), $this->equalTo(15));

        $memcached = new \Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->decrement('foo', 15);
    }

    public function testDestroyMethod()
    {
        $memcache = $this->getMockBuilder('Memcached')->setMethods(['delete'])->getMock();
        $memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $m = new \Cygnite\Cache\Storage\Memcached($memcache);
        $m->destroy('foo');
    }
}
