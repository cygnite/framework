<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Helpers\Inflector;

class InflectorTest extends TestCase
{
    public function testInflectionInstance()
    {
        $this->assertInstanceOf('Cygnite\Helpers\Inflector', new Cygnite\Helpers\Inflector());
    }

    public function testClassifyMethod()
    {
        $this->assertEquals('AdminUser', Inflector::classify('admin_user'));
        $this->assertEquals('AdminUser', Inflector::classify('admin-user'));
    }

    public function testActionPathMethod()
    {
        $this->assertEquals('camel-case-action', Inflector::actionPath('camelCaseAction'));
    }

    public function testPathActionMethod()
    {
        $this->assertEquals('dashSeparated', Inflector::pathAction('dash-separated'));
        $this->assertEquals('dashSeparated', Inflector::pathAction('dash_separated'));
    }

    public function testUnderscoreToSpaceMethod()
    {
        $this->assertEquals('Dash Separated', Inflector::underscoreToSpace('dash_separated'));
    }

    public function testControllerPathMethod()
    {
        $this->assertEquals('pascal-case', Inflector::controllerPath('PascalCase'));
        $this->assertEquals('pascal-case.name', Inflector::controllerPath('PascalCase:name'));
    }

    public function testPathViewMethod()
    {
        $this->assertEquals('PascalCase:Name', Inflector::pathView('pascal-case.name'));
    }

    public function testGetClassNameMethod()
    {
        $this->assertEquals('Inflector', Inflector::getClassName('\Cygnite\Helpers\Inflector'));
    }

    public function testTabilizeMethod()
    {
        $this->assertEquals('user_info', Inflector::tabilize('UserInfo'));
        $this->assertEquals('user', Inflector::tabilize('User'));
    }

    public function testCamelizeMethod()
    {
        $this->assertEquals('userInfo', Inflector::camelize('user_info'));
        $this->assertEquals('fooBarBaz', Inflector::camelize('foo_bar_baz'));
    }

    public function testDeCamelizeMethod()
    {
        $this->assertEquals('foo_bar_baz', Inflector::deCamelize('fooBarBaz'));
    }

    public function testToNamespaceMethod()
    {
        $this->assertEquals('\Foo\Bar\Baz', Inflector::toNamespace('foo.bar.baz'));
    }

    public function testPluralizeMethod()
    {
        $this->assertEquals('Users', Inflector::pluralize('User'));
        $this->assertEquals('Categories', Inflector::pluralize('Category'));
    }

    public function testSingularizeMethod()
    {
        $this->assertEquals('User', Inflector::singularize('Users'));
        $this->assertEquals('Category', Inflector::singularize('Categories'));
    }
}
