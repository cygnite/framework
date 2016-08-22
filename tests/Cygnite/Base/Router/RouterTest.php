<?php

use Cygnite\Base\Router\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{
    private $router;

    private function requestUri($uri)
    {
        $_SERVER['REQUEST_URI'] = $uri;
    }

    private function requestMethod($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
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

    public function setUp()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        // Default request method to GET
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // Default SERVER_PROTOCOL method to HTTP/1.1
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

        $this->router = new Router();
    }

    public function testRouterInstance()
    {
        $this->assertInstanceOf('\Cygnite\Base\Router\Router', new \Cygnite\Base\Router\Router());
    }

    public function testAllRequest()
    {
        $this->getRequest('Hello World!!');
        $this->postRequest('post');
        $this->putRequest('put');
        $this->patchRequest('patch');
        $this->deleteRequest('delete');
        $this->headRequest();
        $this->optionsRequest('options');
    }

    private function getRequest($output = '')
    {
        $this->router->get('/', function () {
            echo 'Hello World!!';
        });

         // Test GET REQUEST
        $this->obStart();
        $this->requestUri('/');
        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function postRequest($output = '')
    {
        $this->router->post('/', function () {
            echo 'post';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('POST');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function putRequest($output = '')
    {
        $this->router->put('/', function () {
            echo 'put';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('PUT');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function patchRequest($output = '')
    {
        $this->router->patch('/', function () {
            echo 'patch';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('PATCH');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function deleteRequest($output = '')
    {
        $this->router->delete('/', function () {
            echo 'delete';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('DELETE');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function headRequest($output = '')
    {
        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('HEAD');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
        $this->obBufferClean();
    }

    private function optionsRequest($output = '')
    {
        $this->router->options('/', function () {
            echo 'options';
        });

        // Test POST REQUEST with Param
        $this->obStart();
        $this->requestMethod('OPTIONS');

        $this->router->run();
        $this->assertEquals($output, ob_get_contents());
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
        $this->assertCount(7, $this->router->getResourceRoutes());
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
}
