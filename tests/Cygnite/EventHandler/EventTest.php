<?php
use Cygnite\EventHandler\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
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
        $this->assertInstanceOf('\Cygnite\EventHandler\Event', $eventInstance);
    }

    public function testEventClouserInstance()
    {
        $event = Event::create(function ($event) {
            $event->register('some', 'Some');

            $event->register('something', function () {
                echo 'Hello_Something';
            });

            $event->register('somethingelse', 'Custom@somethingElse');

            return $event;
        });

        //Testing some function event
        $this->obStart();
        $event->dispatch('some');
        $this->assertEquals('hello world', ob_get_contents());
        $this->obBufferClean();

        //Testing something anynomous function event
        $this->obStart();
        $event->dispatch('something');
        $this->assertEquals('Hello_Something', ob_get_contents());
        $this->obBufferClean();

        //Testing something else class function event
        $this->obStart();
        $event->dispatch('somethingelse');
        $this->assertEquals('Hello_SomeThing_Else', ob_get_contents());
        $this->obBufferClean();
    }

    /**
     * @expectedException \Exception
     */
    public function testFlushEvents()
    {
        $event = Event::create();

        $event->register('order', function () {
            echo 'Order Iphone6';
        });

        $event->remove('order');
        $event->dispatch('order');
    }
}

function Some()
{
    echo 'hello world';
}

class Custom
{
    public function somethingElse()
    {
        echo 'Hello_SomeThing_Else';
    }
}
