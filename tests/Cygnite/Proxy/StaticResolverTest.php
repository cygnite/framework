<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Proxy\StaticResolver;

class StaticResolverTest extends TestCase
{
    public function testCallNotStaticMethodStatically()
    {
        $this->assertEquals('Hello FooBar!', FooStub::fooBar());
    }
}

class FooStub extends StaticResolver
{
    protected function fooBar()
    {
        return 'Hello FooBar!';
    }
}
