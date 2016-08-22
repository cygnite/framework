<?php
use Mockery as m;
use Cygnite\Mvc\View\Widget;

class WidgetTest extends PHPUnit_Framework_TestCase
{
    public function testWidgetInstance()
    {
        $widget = new Widget('foo_bar', []);
        $widget->setModule(false);

        $widgetInstance = Widget::make('foo_bar', [], function ($w) {
            return $w;
        });
        
        $this->assertEquals($widget, $widgetInstance);
    }

    public function testDataSetOnWidgetView()
    {
        $widget = Widget::make('foo_bar', ['foo' => 'bar', 'bar' => 'baz'], function ($w) {
            return $w;
        });

        $this->assertEquals('bar', $widget['foo']);
        $this->assertEquals('baz', $widget['bar']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $widget->getData());

        $widget = Widget::make('foo_bar', [], function ($w) {
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
