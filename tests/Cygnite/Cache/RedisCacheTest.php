<?php
use PHPUnit\Framework\TestCase;

class RedisCacheTest extends TestCase
{
    public function testGetMethodReturnNull()
    {
        $config = [
            'connection' => 'default',
        ];

        $stdClass = $this->getMockBuilder('StdClass')->setMethods(['get'])->getMock();
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

        $stdClass = $this->getMockBuilder('StdClass')->setMethods(['get'])->getMock();
        $redisConnnector = $this->getMockBuilder('\Cygnite\Cache\Storage\RedisConnector')
            ->setConstructorArgs([$stdClass, $config])
            ->getMock();
        $stub = $this->getMockBuilder('Cygnite\Cache\Storage\Redis')->setMethods(['get', 'setConnection'])->getMock();
        $stub->expects($this->any())->method('get')->will($this->returnValue('foo'));
        $this->assertEquals('foo', $stub->get('foo'));
        $this->assertNotNull($stub->get('foo'));
    }
}
