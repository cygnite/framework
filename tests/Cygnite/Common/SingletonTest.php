<?php
use Mockery as m;
use Cygnite\Common\Singleton;

class SingletonTest extends PHPUnit_Framework_TestCase
{
    public function testSingletonInstance()
    {
        $this->assertInstanceOf('Foo', Foo::create());
        $this->assertSame(Foo::create(), Foo::create());
    }

    public function testCalledClass()
    {
        $foo = Foo::create();
        $this->assertEquals('SingletonTest', $foo->called_by());
    }
}


class Foo extends Singleton
{
    public static function create()
    {
        return parent::instance();
    }

    public function called_by()
    {
        return $this->getCalledClass();
    }
}