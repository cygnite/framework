<?php

use Cygnite\Hash\BCrypt;
use Cygnite\Hash\Hash;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    public function testCreateHashByCryptMockInstance()
    {
        $hash = m::mock('Cygnite\Hash\BCrypt');
        $this->assertInstanceOf('Cygnite\Hash\BCrypt', $hash);

        $crypt = Hash::instance();
        $this->assertEquals(BCrypt::make(), $crypt);

        $this->assertInstanceOf('Cygnite\Hash\BCrypt', BCrypt::make());
    }

    public function testHashFunctions()
    {
        $crypt = Hash::create('My_Secret_Key');

        $this->assertNotSame('My_Secret_Key', $crypt);
        $this->assertTrue(Hash::verify('My_Secret_Key', $crypt));
        $this->assertFalse(Hash::needReHash($crypt));
        $this->assertTrue(Hash::needReHash($crypt, ['cost' => 1]));
    }

    public function tearDown()
    {
        m::close();
    }
}
