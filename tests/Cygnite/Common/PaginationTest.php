<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Common\Pagination;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Container\Container;
use Cygnite\Tests\Container\ContainerDependency;

class PaginationTest extends TestCase
{
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

        $this->container['request']->server->add('HTTP_HOST', 'localhost');
        $this->container['request']->server->add('REQUEST_URI', '/');
        Url::setBase('/cygnite/index.php/user');
    }

    public function testMakeMethod()
    {
        $pagination = Pagination::make(function ($p) {
            return $p;
        });
        $this->assertInstanceOf('Cygnite\Common\Pagination', $pagination);
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
