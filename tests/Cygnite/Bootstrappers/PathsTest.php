<?php
use Cygnite\Bootstrappers\Paths;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function testPathInstance()
    {
        $this->assertInstanceOf('Cygnite\Bootstrappers\Paths', new Paths([]));
    }

    public function testPathsSetsIntoPathsObject()
    {
        $this->assertFalse((new Paths([]))->offsetExists('foo'));
        $this->assertTrue((new Paths(['foo' => 'FooBar']))->offsetExists('foo'));
        $p = new Paths(["root" => basename(__DIR__)]);
        $this->assertNotNull($p->offsetGet('root'));
        $this->assertEquals('Bootstrappers', $p->offsetGet('root'));
    }

    public function testAllMethodReturnsArrayOfPaths()
    {
        $p = new Paths([
            "foo" => basename(__DIR__),
            "bar" => 'foo bar',
            "baz" => 'baz bar'
        ]);

        $this->assertEquals([
            "foo" => basename(__DIR__),
            "bar" => 'foo bar',
            "baz" => 'baz bar'
        ], $p->all());
    }


}


