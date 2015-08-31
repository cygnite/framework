<?php
use Mockery as m;
use Cygnite\Mvc\View\View;
use Cygnite\Mvc\View\Template;
use Cygnite\Foundation\Http\Response;

class ViewTest extends PHPUnit_Framework_TestCase
{
    private function view()
    {
        return new View(new Template);
    }

    public function testSetDataOnView()
    {   
        define('CYGNITE_BASE', __DIR__);
        define('APP', '');
        define('APP_NS', 'APP_NS');

        $view = $this->view();
        $data = ['foo' => 'Foo'];        

        $content = $view->render('fixtures.hello', $data, true)->content();        
        $this->assertEquals('Hello Foo', $content);
    }


    public function testViewCreateMethod()
    {
        $data = ['foo' => 'Cygnite!'];
        $content = View::create("fixtures.hello", $data);        
        $this->assertEquals('Hello Cygnite!', Response::make($content)->getContent());

        $data = ['foo' => 'Foo Bar!'];
        $composeContent = View::compose("fixtures.hello", $data);        
        $this->assertEquals('Hello Foo Bar!', Response::make($composeContent)->getContent());
    }

    public function testRenderWithTwigTemplate()
    {
        $view = $this->view();
        $view->setTemplateEngine(true)
             ->setTwigViewPath('')
             ->setAutoReload(true)
             ->setTwigDebug(true);

        $content = $view->render('fixtures.template', ['bar' => 'FooBar'], true);
        
        $this->assertEquals('FooBar', $content);

    }

    public function tearDown()
    {
        m::close();
    }
}
