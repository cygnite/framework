<?php

use Cygnite\Common\Input\Input;

class InputTest extends PHPUnit_Framework_TestCase
{
    protected $input;

    public function setUp()
    {
        $this->input = Input::make();
    }

    public function testInputValue()
    {
        $_POST = [
            'user' => [
                'name'  => 'Foo Bar',
                'email' => 'foo@bar.com',
            ],
        ];
        $this->input->setRequest('post', $_POST);
        $this->assertEquals(['name' => 'Foo Bar', 'email' => 'foo@bar.com'], $this->input->post('user'));
    }

    public function testAccessRequestArrayByDotString()
    {
        $_POST = [
            'user' => [
                'name'  => 'Foo Bar',
                'email' => 'foo@bar.com',
            ],
        ];
        $this->input->setRequest('post', $_POST);
        $this->assertEquals('Foo Bar', $this->input->post('user.name'));
    }

    public function testGetPostValueExceptKey()
    {
        $_POST = [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ];

        $this->input->setRequest('post', $_POST);
        $this->assertEquals(['email' => 'foo@bar.com'], $this->input->except('name')->post());
    }

    public function testIsAjaxRequest()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $_POST = [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ];

        $this->input->setRequest('post', $_POST);
        $this->assertTrue($this->input->isAjax());
    }

    public function testCookieInstance()
    {
        $this->assertInstanceOf('Cygnite\Common\Input\CookieManager\Cookie', $this->input->cookie());
    }

    public function testGetDataAsJson()
    {
    }
}
