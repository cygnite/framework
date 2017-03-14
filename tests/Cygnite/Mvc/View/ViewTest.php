<?php
use Cygnite\Mvc\View\View;
use Cygnite\Mvc\View\Twig\Template;
use Cygnite\Mvc\View\Output;
use Cygnite\Mvc\View\ViewFactory;
use PHPUnit\Framework\TestCase;
use Cygnite\Container\Container;
use Cygnite\Http\Responses\Response;
use Cygnite\Tests\Container\ContainerDependency;

class ViewTest extends TestCase
{
    private $view;

    private $container;

    public function setUp()
    {
        $this->setContainer();
        $this->setPaths();
        $this->makeView();
    }

    public function testSetDataOnView()
    {
        define('CYGNITE_BASE', __DIR__);
        define('APP', '');
        define('APP_NS', 'APP_NS');
        $data = ['foo' => 'Foo'];

        $content = $this->view->render('fixtures.hello', $data, true)->content();
        $this->assertEquals('Hello Foo', $content);
    }

    public function testViewCreateMethod()
    {
        ViewFactory::make(\Cygnite\Mvc\View\View::class, $this->container, function ($v) {
            $v->setContainer($this->container);
        });

        $data = ['foo' => 'Cygnite!'];
        $content = $this->view->create('fixtures.hello', $data);
        $this->assertEquals('Hello Cygnite!', Response::make($content)->getContent());

        $data = ['foo' => 'Foo Bar!'];
        $composeContent = $this->view->compose('fixtures.hello', $data);
        $this->assertEquals('Hello Foo Bar!', Response::make($composeContent)->getContent());
    }

    public function testRenderWithTwigTemplate()
    {

        $view = new View(new Template(), new Output());
        $view/*->setTwigViewPath('Apps.Fistures')*//*
             ->setTemplateLocation()*/
             ->setTemplateEngine(true)
             ->setContainer($this->container)
             ->setTwigViewPath('')
             ->setAutoReload(true)
             ->setTwigDebug(true);

        $content = $view->render('fixtures.template', ['bar' => 'FooBar'], true);

        $this->assertEquals('FooBar', $content);
    }

    private function setContainer()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );
    }

    private function setPaths()
    {
        foreach ((new Paths)->getConfig() as $key => $path) {
            $this->container->set($key, $path);
        }
    }

    private function makeView()
    {
        $this->view = new View(new Template(), new Output());
        $this->view->setContainer($this->container);
    }
}

class Paths
{
    public function getConfig()
    {
        return [
            "root" => realpath(__DIR__),
            "src" => realpath(__DIR__),
            "vendor" => realpath(__DIR__ . "/../vendor"),
            "public" => null,//realpath(__DIR__ . "/../public/"),
            "app.namespace" => "Apps",
            'app.path' => realpath(__DIR__),
            'app.config' => realpath(__DIR__.'/../src/Apps/Configs/'),
            'routes.dir' => realpath(__DIR__.'/../src/Apps/Routing/')
        ];
    }
}
