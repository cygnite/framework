<?php
use Cygnite\Foundation\Application;
use Cygnite\Foundation\Autoloader;
use Mockery as m;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    public function testApplicationInstance()
    {
        $loader = m::mock("Cygnite\Foundation\Autoloader");

        $application = Application::getInstance($loader);

        $this->assertInstanceOf('Cygnite\Foundation\Application', $application);
    }

    public function testSetValueToContainer()
    {
        $loader = m::mock("Cygnite\Foundation\Autoloader");
        $app = Application::getInstance($loader);
        $app->setValue('greet', 'Hello Application');

        $this->assertEquals($app['greet'], 'Hello Application');
    }

    public function testDependencyInjection()
    {
        $loader = m::mock("Cygnite\Foundation\Autoloader");
        $app = Application::getInstance($loader);
        
        $router = new \Cygnite\Base\Router\Router();
        $url = new \Cygnite\Common\UrlManager\Url($router);
        $madeUrl = $app->make('\Cygnite\Common\UrlManager\Url');
        
        $this->assertEquals($url, $madeUrl);
    }

    public function tearDown()
    {
        m::close();
    }
}
