<?php
use Cygnite\Helpers\Config;
use Cygnite\Common\Encrypt;

class EncryptTest extends PHPUnit_Framework_TestCase
{
    protected $crypt;

    public function setUp()
    {
        $this->configure();
        $this->crypt = Encrypt::create();
    }

    private function configure()
    {
        $configuration = [
            'global.config' => [
                "encryption.key"  => 'cygnite-shaXatBNHQ434'
            ]
        ];

        Config::$config = $configuration;
    }

    public function testMakeInstance()
    {
        $crypt = new Encrypt('cygnite-shaXatBNHQ4YEJ32');
        $this->assertInstanceOf('Cygnite\Common\Encrypt', $crypt);
    }

    public function testSecureKey()
    {
        $this->assertNotNull($this->crypt->getKey());
        $this->assertSame(hash('sha256', 'cygnite-shaXatBNHQ434', true), $this->crypt->getKey());
    }

    public function testEncodeString()
    {
        $string = 'PHP secure string';
        $this->assertNotNull($this->crypt->encode($string));
        $this->assertEquals('SVPLBtkxh/xmCfoZpaP0WOn1a3AwhnaB5peaP+TdIP0=', $this->crypt->encode($string));
    }

    public function testDecodeString()
    {
        $string = 'SVPLBtkxh/xmCfoZpaP0WOn1a3AwhnaB5peaP+TdIP0=';
        $this->assertEquals('PHP secure string', $this->crypt->decode($string));
    }
}
