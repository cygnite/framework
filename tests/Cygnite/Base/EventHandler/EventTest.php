<?php
use Cygnite\Foundation\Application;
use Cygnite\Foundation\Autoloader;
use Cygnite\Base\EventHandler\Event;
use Mockery as m;

class EventTest extends PHPUnit_Framework_TestCase
{
    private function obStart()
    {
        ob_start();
    }

    private function obBufferClean()
    {
        ob_clean();
        // Cleanup
        ob_end_clean();
    }

    public function testCreateEventInstance()
    {
        $eventInstance = Event::create();
        $event = m::mock("\Cygnite\Base\EventHandler\Event");

        $loader = m::mock("Cygnite\Foundation\Autoloader");
        $app = Application::getInstance($loader);
        $this->assertInstanceOf('Cygnite\Foundation\Application', $app);
        $app->event = $event;
        $this->assertEquals($event, $app->event);
        $this->assertInstanceOf('\Cygnite\Base\EventHandler\Event', $eventInstance);
    }

    public function testEventClouserInstance()
    {
        $event = Event::create(function ($event) {
            $event->attach('some', 'Some');

            $event->attach('something', function () {
                echo "Hello_Something";
            });

            $event->attach('somethingelse', 'Custom@somethingElse');

            return $event;
        });

        //Testing some function event
        $this->obStart();
        $event->trigger('some');
        $this->assertEquals("hello world", ob_get_contents());
        $this->obBufferClean();
        
        //Testing something anynomous function event
        $this->obStart();
        $event->trigger('something');
        $this->assertEquals("Hello_Something", ob_get_contents());
        $this->obBufferClean();

        //Testing something else class function event
        $this->obStart();
        $event->trigger('somethingelse');
        $this->assertEquals("Hello_SomeThing_Else", ob_get_contents());
        $this->obBufferClean();
    }

    /**
    * @expectedException \Exception
    */
    public function testFlushEvents()
    {
        $event = Event::create();

        $event->attach('order', function () {
            echo "Order Iphone6";
        });

        $event->flush('order');
        $event->trigger('order');
    }
}

function Some()
{
    echo "hello world";
}

class Custom
{
    public function somethingElse()
    {
        echo "Hello_SomeThing_Else";
    }
}
