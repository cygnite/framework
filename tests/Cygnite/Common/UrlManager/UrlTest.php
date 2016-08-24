<?php

use Cygnite\Base\Router\Router;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Foundation\Application;

class UrlTest extends PHPUnit_Framework_TestCase
{
    protected $url;

    private $app;

    public function setUp()
    {
        $this->app = Application::instance();
        $this->app['url'] = new \Cygnite\Common\UrlManager\Url();
        $this->app['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->app['router'] = new \Cygnite\Base\Router\Router($this->app['request']);
        $this->app['router']->setApplication($this->app);
        $this->app['url']->setApplication($this->app);
        $this->url = $this->app['url'];
    }

    public function testUrlManagerInstance()
    {
        $url = Url::make();
        $url->setApplication($this->app);
        $this->assertEquals($this->url, $url);
    }

    public function testUrlSegmentReturnsCorrectly()
    {
        $this->app['request']->server->add('HTTP_HOST', 'localhost');
        $this->app['request']->server->add('SCRIPT_NAME', '/index.php');
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $this->app['request']->server->add('SERVER_PROTOCOL', 'HTTP/1.1');
        $this->app['request']->server->add('REQUEST_URI', '/user/index/2');

        /*$_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_URI'] = '/user/index/2';*/

        $this->assertNotNull(Url::segment('1'));
        $this->assertEquals('2', Url::segment('3'));
    }

    public function testIsSecureProtocol()
    {
        $this->app['request']->server->add('HTTP_HOST', 'localhost');
        $this->app['request']->server->add('SCRIPT_NAME', '/index.php');
        $this->app['request']->server->add('REQUEST_URI', '/user/');
        /*$_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';*/

        $this->assertFalse($this->app['request']->isSecure());
    }

    public function testServerProtocol()
    {
        $this->app['request']->server->add('HTTP_HOST', 'localhost');
        $this->app['request']->server->add('SCRIPT_NAME', '/index.php');
        $this->app['request']->server->add('REQUEST_URI', '/user/');
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $this->app['request']->server->add('SERVER_PROTOCOL', 'HTTP/1.1');

        /*$_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';*/

        $this->assertEquals('http://', $this->url->protocol());
    }

    public function testReferredFrom()
    {
        $this->app['request']->server->add('HTTP_REFERER', 'http://localhost/index.php/home/');
        /*$_SERVER["HTTP_REFERER"] = 'http://localhost/index.php/home/';*/
        $this->assertEquals($this->app['request']->server['HTTP_REFERER'], $this->url->referredFrom());
    }
}
