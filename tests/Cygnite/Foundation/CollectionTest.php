<?php
use Cygnite\Foundation\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
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
        $this->assertInstanceOf('Cygnite\Foundation\Collection', $this->collection);
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

    public function testAddData()
    {
        $this->collection->add('baz', 'Baz Bar');
        $this->assertEquals('Baz Bar', $this->collection->get('baz'));
        $this->assertEquals(['foo' => 'Hello Foo', 'bar' => 'Hello Bar', 'baz' => 'Baz Bar'], $this->collection->all());
    }

    public function testRemoveData()
    {
        $this->collection->remove('baz');
        $this->assertEquals(['foo' => 'Hello Foo', 'bar' => 'Hello Bar'], $this->collection->all());
    }

    public function testEachMethod()
    {
        $this->collection->add('baz', 'baz');
        $original = $this->collection->all();
        $result = [];
        $this->collection->each(function ($value, $key) use (&$result) {
            $result[$key] = $value;
        });

        $this->assertEquals($original, $result);
        
        $collection = new Collection($original = [5, 7, 'foo' => 'bar']);
        $result = [];
        $collection->each(function ($value, $key) use (&$result) {
            if (!is_string($key)) {
                $result[$key] = $value;
                return true;
            }
        });

        $this->assertEquals([5, 7], $result);
    }

    public function testKeysMethod()
    {
        $collection = new Collection($original = [5, 7, 'foo' => 'bar']);
        $this->assertEquals([0, 1, 'foo'], $collection->keys()->all());
    }

    public function testMapMethod()
    {
        $collection = new Collection(['foo' => 'bar', 'bar' => 'baz']);
        $collection = $collection->map(function ($item, $key) {
            return $key.'-'.strrev($item);
        });

        $this->assertEquals(['foo' => 'foo-rab', 'bar' => 'bar-zab'], $collection->all());
    }

    public function testMergeMethod()
    {
        $collection = new Collection(['foo' => 'bar']);
        $data = $collection->merge(['bar' => 'baz']);

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $data->all());
    }

    public function testUniqueMethod()
    {
        $collection = new Collection([1, 2, 'foo' => 'bar', 2, 'bar' => 'baz']);
        $data = $collection->unique();

        $this->assertEquals([1, 2, 'foo' => 'bar','bar' => 'baz'], $data->all());
    }

    //tests reference from Illuminate collection sort
    public function testSortMethodAndWithCallback()
    {
        $data = (new Collection([5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([1, 2, 3, 4, 5], $data->values()->all());
        $data = (new Collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5], $data->values()->all());
        $data = (new Collection(['foo', 'bar_10', 'bar_1']))->sort();
        $this->assertEquals(['bar_1', 'bar_10', 'foo'], $data->values()->all());

        $collection = (new Collection([5, 3, 1, 2, 4]))->sort(function ($x, $y) {
            if ($x === $y) {
                return 0;
            }

            return ($x < $y) ? -1 : 1;
        });
        $this->assertEquals([1,2,3,4,5], array_values($collection->all()));
    }

    public function testShiftMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals('foo', $collection->shift());
    }

    public function testPrepend()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals(['baz-bar', 'foo', 'bar', 'baz'], $collection->prepend('baz-bar')->all());
    }

    public function testFirstMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);

        $this->assertEquals('foo', $collection->first());
    }

    public function testFirstKeyMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);

        $this->assertEquals(0, $collection->firstKey());
    }

    public function testLastMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals('baz', $collection->last());
    }

    public function testReverseMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals(['baz', 'bar', 'foo'], $collection->reverse()->all());
    }

    public function testSearchMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals(1, $collection->search('bar'));
        $this->assertEquals(2, $collection->search('baz', true));
    }

    public function testConvertToArray()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $collection->convertToArray($collection));
    }

    public function testIsEmptyMethod()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertEquals(false, $collection->isEmpty());
    }
}
