<?php

use Cygnite\Foundation\Application;
use Mockery as m;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    public function testApplicationInstance()
    {
        $application = Application::instance();

        $this->assertInstanceOf('Cygnite\Foundation\Application', $application);
    }

    public function testSetValueToContainer()
    {
        $loader = m::mock('Cygnite\Foundation\Autoloader');
        $app = Application::instance();
        $app->set('greet', 'Hello Application');

        $this->assertEquals($app['greet'], 'Hello Application');
    }

    public function testDependencyInjection()
    {
        $this->app = Application::instance();
        $this->app['url'] = new \Cygnite\Common\UrlManager\Url();
        $this->app['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->app['router'] = new \Cygnite\Base\Router\Router($this->app['request']);
        $this->app['router']->setApplication($this->app);
        $this->app['url']->setApplication($this->app);

        $madeUrl = $this->app->make('\Cygnite\Common\UrlManager\Url');
        $madeUrl->setApplication($this->app);

        $this->assertEquals($this->app['url'], $madeUrl);
    }

    public function testServiceCreation()
    {
        $app = Application::instance();
        $app->registerServiceProvider(['FooBarServiceProvider']);
        $app->setServiceController('bar.controller', 'BarController');

        $this->assertInstanceOf('\FooBar', $app['foo.bar']());
        $this->assertNotNull($app['foo.bar']()->greet());
        $this->assertEquals('Hello FooBar!', $app['foo.bar']()->greet());

        $app['greet.bar.controller'] = 'Hello BarController!';
        $this->assertEquals('Hello BarController!', $app['bar.controller']()->indexAction());
    }

    public function testComposeMethod()
    {
        $app = Application::instance();
        $bazBar = $app->compose('BazBar', ['greet' => 'Hello!']);

        $this->assertArrayHasKey('greet', $app);
        $this->assertEquals('Hello!', $bazBar->greet());
    }

    public function tearDown()
    {
        m::close();
    }
}

class FooBarServiceProvider
{
    protected $app;

    public function register(Application $app)
    {
        $app['foo.bar'] = $app->share(function ($c) {
            return new FooBar();
        });
    }
}

class FooBar
{
    public function greet()
    {
        return 'Hello FooBar!';
    }
}

class BarController
{
    private $app;

    private $serviceController;

    public function __construct($serviceController, \Cygnite\Foundation\ApplicationInterface $app)
    {
        $this->app = $app;
    }

    public function indexAction()
    {
        return $this->app['greet.bar.controller'];
    }
}

class BazBar
{
    private $arguments = [];

    public function __construct($arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function greet()
    {
        return $this->arguments['greet'];
    }
}
