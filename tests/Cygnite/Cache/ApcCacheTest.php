<?php
use Mockery as m;
use Cygnite\Cache\Factory\Cache;

class ApcCacheTest extends PHPUnit_Framework_TestCase
{
    private $apc;

    public function setUp()
    {
        $this->apc = Cache::make('apc', function ($apc) {
            return $apc;
        });
    }

    public function testInstance()
    {
        $this->assertInstanceOf('\Cygnite\Cache\Storage\Apc', $this->apc);
    }

    public function testStoreData()
    {
        $this->apc->store('foo', 'Foo Bar');
        $this->assertEquals('Foo Bar', $this->apc->get('foo'));
    }
}
