<?php
use Cygnite\Helpers\Config;
use Cygnite\Common\SessionManager\Native\Session;

class NativeSessionTest extends PHPUnit_Framework_TestCase
{
    protected $session;

    public function setUp()
    {
        // Fix for header set already
        $prev = error_reporting(0);
        session_start();
        error_reporting($prev);

        $configuration = [
            'config.session' => [
                'session_name'  => 'cf_secure_session',
                'use_session_cookie' => false,
                'httponly' => true,
                'secure' => false,
            ]
        ];

        Config::$config = $configuration;

        $this->session = new Session('Cygnite', null, new Cygnite\Common\SessionManager\Session());
    }
    /**
     * @outputBuffering enabled
     */
    public function testSetItemIntoSession()
    {

        $this->session->set('name', "John Doe");

        $this->assertEquals('John Doe', $this->session->get('name'));

        $this->session->foo = ['bar' => 'Foo Bar'];
        $this->assertEquals(['bar' => 'Foo Bar'], $this->session->foo);
    }

    public function testGetItemFromSession()
    {
        $this->session->set('greet', "Welcome John!");

        $this->assertEquals('Welcome John!', $this->session->get('greet'));
        $this->assertEquals('Welcome John!', $this->session->greet);
    }

    public function testHasItemStoredOnSession()
    {
        $this->session->set('greet', "Welcome John!");

        $this->assertTrue($this->session->has('greet'));
    }

    public function testGetAllItemFromSession()
    {
        $this->assertArrayHasKey('greet', $this->session->all());
        $this->assertEquals($this->session->get(), $this->session->all());
    }

    public function testDeleteItemFromSession()
    {
        $this->session->delete('foo');
        $this->assertArrayNotHasKey('foo', $this->session->all());
    }

    public function testResetMethod()
    {
        $this->session->reset();
        $this->assertEmpty($this->session->all());
    }
}
