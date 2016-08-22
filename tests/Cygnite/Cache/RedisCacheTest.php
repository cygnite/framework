<?php
use Mockery as m;
use Cygnite\Helpers\Config;
use Cygnite\Cache\Factory\Cache;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    public function testGetMethodReturnNull()
    {
        $config = [
            'connection' => 'default',
        ];

        $stdClass = $this->getMock('StdClass', ['get']);

        $redisConnnector = $this->getMockBuilder('\Cygnite\Cache\Storage\RedisConnector')
            ->setConstructorArgs([$stdClass, $config])
            ->setMethods(['get'])
            ->getMock();

        $redisConnnector->expects($this->any())->method('get')->will($this->returnValue(null));
        $cache = new Cygnite\Cache\Storage\Redis($redisConnnector);

        $this->assertNull($cache->get('foo'));
    }

    public function testGetMethodReturnValue()
    {
        $config = [
                        'connection' => 'default',
        ];

        $stdClass = $this->getMock('StdClass', ['get']);
        $redisConnnector = $this->getMockBuilder('\Cygnite\Cache\Storage\RedisConnector')
            ->setConstructorArgs([$stdClass, $config])
            ->getMock();
        $stub = $this->getMock('Cygnite\Cache\Storage\Redis', ['get', 'setConnection']);
        $stub->expects($this->any())->method('get')->will($this->returnValue('foo'));
        $this->assertEquals('foo', $stub->get('foo'));
        $this->assertNotNull($stub->get('foo'));
    }
}
