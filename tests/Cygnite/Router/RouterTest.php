<?php
use Cygnite\Container\Container;
use PHPUnit\Framework\TestCase;
use Cygnite\Tests\Container\ContainerDependency;

class RouterTest extends TestCase
{
    private $router;

    private $request;

    private $container;

    public function setUp()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );

        $this->container->make(\Cygnite\Router\Router::class);
        $this->router = $this->container->get('router');
        $this->request = \Cygnite\Http\Requests\Request::createFromGlobals($_GET, $_POST);
        $this->request->server->add('SCRIPT_NAME', '/index.php');
        $this->request->server->add('REQUEST_METHOD', 'GET');
        $this->request->server->add('SERVER_PROTOCOL', 'HTTP/1.1');
        $this->router->setRequest($this->request);

        $this->router->setContainer($this->container);
    }

    private function requestUri($uri)
    {
        $this->request->server->add('REQUEST_URI', $uri);
    }

    private function requestMethod($method)
    {
        $this->request->server->add('REQUEST_METHOD', $method);
    }

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

    public function testGetRequest()
    {
        $this->router->get('/', function () {
            echo 'Hello World!!';
        });

         // Test GET REQUEST
        $this->obStart();
        $this->requestUri('/');
        $this->router->run();
        $this->assertEquals('Hello World!!', ob_get_contents());
        $this->obBufferClean();
    }

    public function testPostRequest()
    {
        $this->router->post('/post', function () {
            echo 'post request';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestUri('/post');
        $this->requestMethod('POST');
        $this->router->run();
        $this->assertEquals('post request', ob_get_contents());
        $this->obBufferClean();
    }

    public function testPutRequest()
    {
        $this->router->put('/put', function () {
            echo 'put';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestUri('/put');
        $this->requestMethod('PUT');

        $this->router->run();
        $this->assertEquals('put', ob_get_contents());
        $this->obBufferClean();
    }

    public function testPatchRequest()
    {
        $this->router->patch('/patch', function () {
            echo 'patch';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestUri('/patch');
        $this->requestMethod('PATCH');

        $this->router->run();
        $this->assertEquals('patch', ob_get_contents());
        $this->obBufferClean();
    }

    public function testDeleteRequest()
    {
        $this->router->delete('/delete', function () {
            echo 'delete';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestUri('/delete');
        $this->requestMethod('DELETE');

        $this->router->run();
        $this->assertEquals('delete', ob_get_contents());
        $this->obBufferClean();
    }

    public function headRequest()
    {
        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('HEAD');

        $this->router->run();
        $this->assertEquals('', ob_get_contents());
        $this->obBufferClean();
    }

    public function optionsRequest()
    {
        $this->router->options('/', function () {
            echo 'options';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('OPTIONS');

        $this->router->run();
        $this->assertEquals('options', ob_get_contents());
        $this->obBufferClean();
    }


    public function testGetRequestDynamicRouteWithParameter()
    {
        $this->router->get('/hello/{:name}', function ($router, $name) {
            echo "Hello $name";
        });

         // Test GET REQUEST with Param
        $this->obStart();
        $this->requestUri('/hello/Cygnite');
        $this->router->run();
        $this->assertEquals('Hello Cygnite', ob_get_contents());
        $this->obBufferClean();
    }

    public function testAnyRouteRequest()
    {
        $this->router->any('/', function () {
            echo 'any_request';
        });

        //We will Test GET
        $this->obStart();
        $this->requestUri('/');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();

        //Testing POST method
        $this->obStart();
        $this->requestMethod('POST');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();

        // Testing PUT method
        $this->obStart();
        $this->requestMethod('PUT');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();

        // Testing PATCH method
        $this->obStart();
        $this->requestMethod('PATCH');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();

        // Test Delete
        $this->obStart();
        $this->requestMethod('DELETE');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();

        // Testing Head method
        $this->obStart();
        $this->requestMethod('HEAD');
        $this->router->run();
        $this->assertEquals('', ob_get_contents());
        $this->obBufferClean();

        // Test Options method
        $this->obStart();
        $this->requestMethod('OPTIONS');
        $this->router->run();
        $this->assertEquals('any_request', ob_get_contents());
        $this->obBufferClean();
    }

    public function testWherePattern()
    {
        $this->router->where('{:some_pattern}', 'some_pattern');

        $this->assertEquals('some_pattern', $this->router->getPattern('{:some_pattern}'));
    }

    public function testGroupRouting()
    {
        $this->router->group('/photos', function ($route) {
            $route->get('/overview/', function () {
                echo 'Photos_Overview';
            });

            $route->get('/abstract/{:id}', function ($route, $id) {
                echo "Photo_Abstract_ID:_$id";
            });

            $route->where('{:any_string}', '(\w+)')->get('cygnite/{:any_string}', function ($route, $string) {
                echo "Photo_Where_Image_Name:_$string";
            });
        });

        $this->obStart();
        $this->requestUri('/photos/overview/');
        $this->router->run();
        $this->assertEquals('Photos_Overview', ob_get_contents());
        $this->obBufferClean();

        $this->obStart();
        $this->requestUri('/photos/cygnite/Cygnite');
        $this->router->run();
        $this->assertEquals('Photo_Where_Image_Name:_Cygnite', ob_get_contents());
        $this->obBufferClean();
    }

    public function testResourceControllerRoutes()
    {
        $this->router->resource('photos', 'photo');
        $this->assertCount(7, $this->router->getRouteResourceController()->getResourceRoutes());
    }

    public function testBeforeRoutingFilter()
    {
        $this->router->before('GET', '/{:all}', function () {
            echo 'before_routing_middleware';
        });

        $this->obStart();
        $this->requestUri('/hello/');
        $this->router->run();
        $this->assertEquals('before_routing_middleware', ob_get_contents());
        $this->obBufferClean();
    }

    public function testAfterRoutingFilter()
    {
        $this->router->get('/hello/', function () {
            echo 'hello_';
        });

        $this->router->after(function () {
            echo 'after_routing_middleware';
        });

        $this->obStart();
        $this->requestUri('/hello/');
        $this->router->run();
        $this->assertEquals('hello_after_routing_middleware', ob_get_contents());
        $this->obBufferClean();
    }

    public function test404PageError()
    {
        $this->router->get('/user/', function () {
            echo 'Hello';
        });

        $this->router->set404Page(function () {
            echo 'Abort 404 Page Not Found!';
        });

        $this->obStart();
        $this->requestUri('/hello/');
        $this->router->run();
        $this->assertEquals('Abort 404 Page Not Found!', ob_get_contents());
        $this->obBufferClean();
    }

    public function testResourceController()
    {
        $this->router->getRouteControllerObject()->setControllerNamespace("Cygnite\\Tests\\Router\\");
        $this->router->resource('person', 'user');
        $this->obStart();
        $this->requestUri('/person');
        $this->router->run();
        $this->assertEquals('Hello User', ob_get_contents());
        $this->obBufferClean();
    }
}
