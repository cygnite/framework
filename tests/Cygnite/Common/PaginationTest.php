<?php
use Cygnite\Common\Pagination;
use Cygnite\Base\Router\Router;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Foundation\Application;

class PaginationTest extends PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = Application::instance();
        $this->app['url'] = new \Cygnite\Common\UrlManager\Url();
        $this->app['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->app['router'] = new \Cygnite\Base\Router\Router($this->app['request']);
        $this->app['router']->setApplication($this->app);
        $this->app['url']->setApplication($this->app);

        $this->app['request']->server->add('HTTP_HOST', 'localhost');
        $this->app['request']->server->add('REQUEST_URI', '/');
/*
        $app = Application::instance();
        $app['router'] = new Router;*/
        Url::setBase("/cygnite/index.php/user");
    }

    public function testMakeMethod()
    {
        $this->assertInstanceOf('Cygnite\Common\Pagination', Pagination::make());
    }

    public function testPagiationCreateLinks()
    {
        $pagination = new Pagination();
        $pagination->setTotalNumberOfPage(6);
        $pagination->setPerPage(2);

        $this->assertNotNull($pagination->createLinks());
        $this->assertEquals(
            trim("<div class='pagination'><span class='disabled'> previous</span><span class='current'>1</span><a href='http://localhost/cygnite/index.php/user/index/2'>2</a><a href='http://localhost/cygnite/index.php/user/index/3'>3</a><a href='http://localhost/cygnite/index.php/user/index/2'>next </a></div>"),
            trim($pagination->createLinks())
        );
    }
}

class User
{
    public $perPage = 2;
}
