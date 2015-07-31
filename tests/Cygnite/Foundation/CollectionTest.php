<?php
use Mockery as m;
use Cygnite\Foundation\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    private $data = [];

    private $collection;
    
    public function setUp()
    {
        $this->data = ['foo' => 'Hello Foo', 'bar' => 'Hello Bar'];

        $this->collection = new Collection($this->data);
    }
    
    public function testCollectionInstance()
    {
        $collection = m::mock('Cygnite\Foundation\Collection');
        $this->assertInstanceOf('Cygnite\Foundation\Collection', $collection);
    }
    
    public function testGetDataCollection()
    {
        $this->assertEmpty(!$this->collection->getData());
        $this->assertEquals($this->data, $this->collection->getData());
    }
    
    public function testCollectionMethods()
    {
        $this->assertEquals($this->data, $this->collection->asArray());
        $this->assertEquals(json_encode($this->data), $this->collection->asJson());
        $this->assertEquals(count($this->data), $this->collection->count());
        $this->assertEquals(serialize($this->data), $this->collection->serialize());
    }

    public function tearDown()
    {
        m::close();
    }
}
