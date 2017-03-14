<?php
use Cygnite\Mvc\View\Widget;
use PHPUnit\Framework\TestCase;
use Cygnite\Mvc\View\Output;
use Cygnite\Bootstrappers\Paths;
use Mockery as m;

class WidgetTest extends TestCase
{
    private $widget;

    public function setUp()
    {
        $this->widget = new Widget(new Paths([
            "root" => realpath(__DIR__),
            "src" => realpath(__DIR__),
            "public" => realpath(__DIR__ . "/../public/"),
            "app.namespace" => "Apps",
            'app.path' => realpath(__DIR__)
        ]), new Output());
    }

    public function testMakeMethodReturnsString()
    {
        $this->widget->setModule(false);
        $content = $this->widget->make('foo_bar', [], function ($w) {
            return 'Hello World!';
        });

        $this->assertEquals("Hello World!", $content);
    }

    public function testDataSetOnWidgetView()
    {
        $widget = $this->widget->make('fixtures:hello', ['foo' => 'bar', 'bar' => 'baz'], function ($w) {
            return $w;
        });

        $this->assertEquals('bar', $widget['foo']);
        $this->assertEquals('baz', $widget['bar']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $widget->getData());
        $widget = $this->widget->make('foo_bar', [], function ($w) {
            $w['var'] = 'user';
            return $w;
        });

        $widget['foobaz'] = 'Foo Baz';

        $this->assertEquals(['var' => 'user', 'foobaz' => 'Foo Baz'], $widget->getData());
    }

    public function testRenderWidget()
    {
        $widget = m::mock('Cygnite\Mvc\View\Widget');

        $widget->shouldReceive('make')
               ->with('mywidget', [], m::type('Closure'))
               ->once()->andReturn($params = ['foo' => 'bar']);

        $contents = $widget->make('mywidget', [], function () use ($params) {
            return $params;
        });
        $this->assertEquals($params, $contents);
    }

    public function tearDown()
    {
        m::close();
    }
}
