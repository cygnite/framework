<?php
use Mockery as m;
use Cygnite\Http\Responses\Response;
use Cygnite\Http\Responses\JsonResponse;

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function testResponseInstance()
    {
        $response = Response::make("Hello World");
        $r = new Response();
        $r->setContent("Hello World");

        $this->assertEquals($r, $response);
        $this->assertInstanceOf('\Cygnite\Http\Responses\Response', $response);
    }

    public function testReturnsContentCorrectly()
    {
        $string = 'Foo Bar';
        $response = new Response();
        $response->setContent($string);
        $this->assertSame($string, $response->getContent());
    }

    public function testReturnsStatusCodeCorrectly()
    {
        $response = new Response();
        $response->setStatusCode(500);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testReturnsJsonResponse()
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->getContentType());
    }
}