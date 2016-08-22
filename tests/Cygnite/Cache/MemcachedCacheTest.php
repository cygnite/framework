<?php


class MemcachedCacheTest extends PHPUnit_Framework_TestCase
{
    public function testStoreData()
    {
        $memcacheObject = $this->getMock('StdClass', ['set', 'get', 'getResultCode']);

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
        $memcacheObject = $this->getMock('StdClass', ['get', 'getResultCode']);

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
        $memcache = $this->getMock('Memcached', ['increment']);
        $memcache->expects($this->once())
                 ->method('increment')
                 ->with($this->equalTo('foo'), $this->equalTo(3));

        $memcached = new Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->increment('foo', 3);
    }

    public function testDecrementMethod()
    {
        $memcache = $this->getMock('Memcached', ['decrement']);
        $memcache->expects($this->once())
                 ->method('decrement')
                 ->with($this->equalTo('foo'), $this->equalTo(15));

        $memcached = new \Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->decrement('foo', 15);
    }

    public function testDestroyMethod()
    {
        $memcache = $this->getMock('Memcached', ['delete']);
        $memcache->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $m = new \Cygnite\Cache\Storage\Memcached($memcache);
        $m->destroy('foo');
    }
}
