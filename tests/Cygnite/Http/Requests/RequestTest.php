<?php

use Cygnite\Http\Requests\Request;
use Cygnite\Http\Requests\RequestHeaderConstants;

class RequestTest extends PHPUnit_Framework_TestCase
{
    private $request;

    public function setUp()
    {
        $this->request = new Request($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES, $_ENV);
    }

    public function testResponseInstance()
    {
        $request = Request::createFromGlobals();
        $this->assertInstanceOf('\Cygnite\Http\Requests\Request', $request);
    }

    public function testDetectingHttpMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $request = Request::createFromGlobals();
        $this->assertEquals('PATCH', $request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = Request::createFromGlobals();
        $this->assertEquals('GET', $request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Request::createFromGlobals();
        $this->assertEquals('POST', $request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $request = Request::createFromGlobals();
        $this->assertEquals('PUT', $request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $request = Request::createFromGlobals();
        $this->assertEquals('DELETE', $request->getMethod());

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $request = Request::createFromGlobals();
        $this->assertEquals('OPTIONS', $request->getMethod());
    }

    public function testReturnsHttpHeaderValueCorrectly()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_CONTENT_LENGTH'] = 11;

        $request = Request::createFromGlobals();
        $this->assertEquals('application/json', $request->header->get('CONTENT_TYPE'));
        $this->assertEquals(11, $request->header->get('CONTENT_LENGTH'));
    }

    public function testReturnsHttpHeaderWithMockArray()
    {
        $server = [];
        $server['HTTP_CONTENT_TYPE'] = 'application/json';
        $server['HTTP_CONTENT_LENGTH'] = 11;

        $request = Request::createFromGlobals(null, null, null, $server, null, null, null);
        $this->assertEquals('application/json', $request->header->get('CONTENT_TYPE'));
        $this->assertEquals(11, $request->header->get('CONTENT_LENGTH'));
    }

    public function testIfRequestVariableIsSet()
    {
        $_GET['foo'] = 'bar';
        $this->request->query->exchangeArray($_GET);
        $this->assertTrue($this->request->query->has('foo'));

        $_POST['foo'] = 'bar';
        $this->request->getPost()->exchangeArray($_POST);
        $this->assertTrue($this->request->post->has('foo'));
    }

    public function testIfGetVariableIsNotSet()
    {
        //$this->assertFalse($this->request->query->has("foo"));
        //$this->assertSame(false, $this->request->query->has("foo"));
    }

    public function testIfPostVariableIsNotSet()
    {
        //$this->assertFalse($this->request->post->has("foo"));
        //$this->assertSame(false, $this->request->post->has("foo"));
    }

    public function testIfPatchVariableIsNotSet()
    {
        $this->assertFalse($this->request->patch->has('foo'));
    }

    public function testPathMethod()
    {
        $request = Request::create('/', 'GET');
        $this->assertEquals('/', $request->getPath());
        $request = Request::create('/foo/bar', 'GET');
        $this->assertEquals('/foo/bar', $request->getPath());
    }

    public function testFullUrlMethod()
    {
        $request = Request::create('https://xyz.com/', 'GET');
        $this->assertEquals('https://xyz.com/', $request->getFullUrl());

        $request = Request::create('https://xyz.com/?a=b', 'GET');
        $this->assertEquals('https://xyz.com/?a=b', $request->getFullUrl());

        $request = Request::create('http://xyz.com/foo/bar?name=sanjoy', 'GET');
        $this->assertEquals('http://xyz.com/foo/bar?name=sanjoy', $request->getFullUrl());
    }

    public function testCurrentUri()
    {
        $_SERVER['HTTP_HOST'] = 'https://xyz.com';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $request = Request::createFromGlobals();
        $this->assertEquals('/foo/bar', $request->getCurrentUri());
    }

    public function testIfClientIpAddressSet()
    {
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.23.2';
        Request::setTrustedHeaderName(RequestHeaderConstants::CLIENT_IP, 'HTTP_CLIENT_IP');
        $request = Request::createFromGlobals();
        $this->assertEquals('192.168.23.2', $request->getClientIPAddress());
    }

    public function testIsAjaxRequest()
    {
        $request = Request::create('/', 'GET');
        $this->assertFalse($request->isAjax());

        $request = Request::create('/', 'POST');
        $request->header->set('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isAjax());

        $request->header->set('X-Requested-With', '');
        $this->assertFalse($request->isAjax());

        $request = Request::create('/', 'GET', [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($request->isAjax());
    }

    public function testIsSecureRequestMethod()
    {
        $request = Request::create('http://xyz.com', 'GET');
        $this->assertFalse($request->isSecure());
        $request = Request::create('https://xyz.com', 'GET');
        $this->assertTrue($request->isSecure());
    }

    public function testGetUser()
    {
        $request = Request::create('http://user_test:password_test@test.com/', 'GET');
        $user = $request->getUser();
        $this->assertEquals('user_test', $user);
    }

    public function testGetPassword()
    {
        $request = Request::create('http://user_test:password_test@test.com/', 'GET');
        $password = $request->getPassword();
        $this->assertEquals('password_test', $password);
    }

    public function testClientProtoPortWithTrustedProxy()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.23.1';
        $_SERVER['X-Forwarded-Proto'] = 'https';
        Request::setTrustedProxies('192.168.23.1');
        Request::setTrustedHeaderName(RequestHeaderConstants::CLIENT_PROTO, 'X-Forwarded-Proto');

        $this->assertEquals(443, Request::createFromGlobals()->getPort());
    }

    public function testQueryHasMethod()
    {
        $request = Request::create('/', 'GET', ['foo' => 'bar']);

        $this->assertTrue($request->query->has('foo'));
        $this->assertFalse($request->query->has('baz'));

        $request = Request::create('/', 'GET', ['foo' => ['bar', 'bar']]);
        $this->assertTrue($request->query->has('foo'));

        $request = Request::create('/', 'GET', ['foo' => '', 'bar' => null]);
        $this->assertTrue($request->query->has('foo'));
        $this->assertEquals(null, $request->query->has('bar'));
        $this->assertFalse($request->query->has('bar'));
    }

    public function testGetHostAndPortMethod()
    {
        $request = Request::create('http://foo.com', 'GET', [], [], ['HTTP_HOST' => 'www.example.com']);
        $this->assertSame('foo.com', $request->getHost());
        $request = Request::create('http://foo.com', 'GET');
        $this->assertSame(80, $request->getPort());

        $request = Request::create('http://example.com:8500', 'GET');
        $this->assertEquals(8500, $request->getPort());

        $request = Request::create('https://example.com', 'GET');
        $this->assertEquals(443, $request->getPort());
    }

    public function testSetHeaderReturnsExpectedLanguage()
    {
        $request = Request::createFromGlobals();
        $request->header->set('Accept-language', 'zh, en-us; q=0.8, en; q=0.6');

        $this->assertContains('zh, en-us; q=0.8, en; q=0.6', $request->header->get('accept-language'));
    }

    public function testSettingContentTypeForUnsupportedMethods()
    {
        $put = Request::create('/url', 'PUT');
        $this->assertEquals('application/x-www-form-urlencoded', $put->server->get('CONTENT_TYPE'));

        $patch = Request::create('/url', 'PATCH');
        $this->assertEquals('application/x-www-form-urlencoded', $patch->server->get('CONTENT_TYPE'));

        $delete = Request::create('/url', 'DELETE');
        $this->assertEquals('application/x-www-form-urlencoded', $delete->server->get('CONTENT_TYPE'));
    }

    //public function test
}
