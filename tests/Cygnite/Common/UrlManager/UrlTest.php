<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Container\Container;
use Cygnite\Tests\Container\ContainerDependency;

class UrlTest extends TestCase
{
    protected $url;

    private $container;

    public function setUp()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );

        $this->container['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->container['router'] = $this->container->make(\Cygnite\Router\Router::class);
        $this->container['router']->setContainer($this->container);
        $this->container['router']->setRequest($this->container['request']);
        $this->url = new \Cygnite\Common\UrlManager\Url(new \Cygnite\Common\UrlManager\Manager($this->container));
    }

    public function testUrlManagerInstance()
    {
        $url = new Url(new \Cygnite\Common\UrlManager\Manager($this->container));
        $this->assertEquals($this->url, $url);
    }

    public function testUrlSegmentReturnsCorrectly()
    {
        $this->container['request']->server->add('HTTP_HOST', 'localhost');
        $this->container['request']->server->add('SCRIPT_NAME', '/index.php');
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $this->container['request']->server->add('SERVER_PROTOCOL', 'HTTP/1.1');
        $this->container['request']->server->add('REQUEST_URI', '/user/index/2');

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
        $this->container['request']->server->add('HTTP_HOST', 'localhost');
        $this->container['request']->server->add('SCRIPT_NAME', '/index.php');
        $this->container['request']->server->add('REQUEST_URI', '/user/');
        /*$_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';*/

        $this->assertFalse($this->container['request']->isSecure());
    }

    public function testServerProtocol()
    {
        $this->container['request']->server->add('HTTP_HOST', 'localhost');
        $this->container['request']->server->add('SCRIPT_NAME', '/index.php');
        $this->container['request']->server->add('REQUEST_URI', '/user/');
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $this->container['request']->server->add('SERVER_PROTOCOL', 'HTTP/1.1');

        /*$_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/user/';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';*/

        $this->assertEquals('http://', $this->url->protocol());
    }

    public function testReferredFrom()
    {
        $this->container['request']->server->add('HTTP_REFERER', 'http://localhost/index.php/home/');
        /*$_SERVER["HTTP_REFERER"] = 'http://localhost/index.php/home/';*/
        $this->assertEquals($this->container['request']->server['HTTP_REFERER'], $this->url->referredFrom());
    }
}
