<?php
use Mockery as m;
use Cygnite\Helpers\Config;
use Cygnite\Cache\Factory\Cache;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
	private $redis;

	public function setUp()
	{
        $configuration = [
            'global.config' => [
                'cache' => [
                    'redis' => [
                        'connection' => 'default',
                    ],
                ]    
            ]
        ];

        Config::$config = $configuration;

		$this->redis = Cache::make('redis', function ($redis)
        {
        	return $redis;
        });
	}

    public function testInstance()
    {
        $this->assertInstanceOf('\Cygnite\Cache\Storage\Redis', $this->redis);
    }

    public function testStoreData()
    {
    	$this->redis->store('foo', 'Foo Bar');
    	$this->assertEquals('Foo Bar', $this->redis->get('foo'));
    }

    public function testReturnNullWhenItemNotFound()
    {
        $this->redis->store('foobar', 'Hello Foo!');
        $this->assertNull($this->redis->get('bar'));
    }

    public function testReternEqualValue()
    {
        $this->redis->store('foo.baz', 'Hello FooBaz!');
        $this->assertEquals('Hello FooBaz!', $this->redis->get('foo.baz'));
    }

    public function testReturnNumericValue()
    {
        $this->redis->store('baz', 3);
        $this->assertEquals('3', $this->redis->get('baz'));
    }

    public function testIncrementMethod()
    {
        $this->redis->store('baz', 3);
        $this->redis->increment('baz');
        
        $this->assertEquals('4', $this->redis->get('baz'));
    }

    public function testDecrementMethod()
    {
        $this->redis->store('baz', 3);
        $this->redis->decrement('baz');
        
        $this->assertEquals('2', $this->redis->get('baz'));
    }

    public function testDestroyMethod()
    {
        $this->redis->store('foobaz', 'Hello');
        $this->redis->destroy('foobaz');
        $this->assertNull($this->redis->get('foobaz'));
    }

    public function tearDown()
    {
        m::close();
    }
}
