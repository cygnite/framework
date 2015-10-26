<?php
use Mockery as m;
use Cygnite\Foundation\Application;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Base\Router\Router;

class UrlTest extends PHPUnit_Framework_TestCase
{
    protected $url;

    public function setUp()
    {
        $app = Application::instance();
        $app['router'] = new Router();
        $this->url = new Url($app['router']);
    }

    public function testUrlManagerInstance()
    {
        $this->assertEquals($this->url, Url::make());
    }

    public function testUrlSegmentReturnsCorrectly()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_URI'] = '/user/index/2';

        $this->assertNotNull(Url::segment('1'));
        $this->assertEquals('2', Url::segment('3'));
    }

    public function testIsSecureProtocol()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';

        $this->assertFalse($this->url->isSecure());
    }

    public function testServerProtocol()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

        $this->assertEquals('http://', $this->url->protocol());
    }

    public function testReferredFrom()
    {
        $_SERVER["HTTP_REFERER"] = 'http://localhost/index.php/home/';
        $this->assertEquals($_SERVER["HTTP_REFERER"], $this->url->referredFrom());
    }
}