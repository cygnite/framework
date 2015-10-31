<?php
use Cygnite\Proxy\StaticResolver;

class StaticResolverTest extends PHPUnit_Framework_TestCase
{
    public function testReturnAppInstance()
    {
        $fooBar = new FooStub();
        $this->assertInstanceOf('Cygnite\Foundation\Application', $fooBar->app());
    }

    public function testCallNotStaticMethodStatically()
    {
        $this->assertEquals('Hello FooBar!', FooStub::fooBar());
    }
}

class FooStub extends StaticResolver
{
    protected function fooBar()
    {
        return "Hello FooBar!";
    }
}