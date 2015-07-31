<?php
use Mockery as m;
use Cygnite\Helpers\Config;
use Cygnite\Cache\Factory\Cache;

class MemcachedCacheTest extends PHPUnit_Framework_TestCase
{
    private $memcached;

    public function setUp()
    {
        $configuration = [
            'global.config' => [
                'cache' => [

                    'memcached' => [

                        'autoconnnect' => true,

                        'servers' => [
                            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 50]
                        ],
                    ],
                ]
            ]
        ];

        Config::$config = $configuration;

        $this->memcached = Cache::make('memcached', function ($memcached) {
            return $memcached;
        });
    }

    public function testInstance()
    {
        $this->assertInstanceOf('\Cygnite\Cache\Storage\Memcached', $this->memcached);
    }

    public function testStoreData()
    {
        $this->memcached->store('foo', 'Foo Bar');
        $this->assertEquals('Foo Bar', $this->memcached->get('foo'));
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

        $memcached = new Cygnite\Cache\Storage\Memcached($memcacheObject);
        $this->assertNull($memcached->get('bar'));
    }

    public function testIncrementMethod()
    {
        $this->memcached->store('foo', 10);
        $this->memcached->increment('foo', 5);

        $this->assertEquals(15, $this->memcached->get('foo'));

        $memcache = $this->getMock('Memcached', ['increment']);
        $memcache->expects($this->once())
                 ->method('increment')
                 ->with($this->equalTo('foo'), $this->equalTo(3));

        $memcached = new Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->increment('foo', 3);
    }

    public function testDecrementMethod()
    {
        $this->memcached->store('foo', 10);
        $this->memcached->decrement('foo', 5);

        $this->assertEquals(5, $this->memcached->get('foo'));


        $memcache = $this->getMock('Memcached', ['decrement']);
        $memcache->expects($this->once())
                 ->method('decrement')
                 ->with($this->equalTo('foo'), $this->equalTo(15));

        $memcached = new Cygnite\Cache\Storage\Memcached($memcache);
        $memcached->decrement('foo', 15);
    }

    public function testDestroyMethod()
    {
        $this->memcached->store('foo', 10);
        $this->memcached->store('bar', 'Hello Bar');
        $this->memcached->decrement('foo', 5);

        $this->memcached->destroy('foo');
        $this->assertNull($this->memcached->get('foo'));
        $this->assertEquals('Hello Bar', $this->memcached->get('bar'));
        $this->memcached->flush();
        $this->assertNull($this->memcached->get('bar'));
    }
}
