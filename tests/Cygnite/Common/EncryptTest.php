<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Common\Encrypt;
use Cygnite\Helpers\Config;

/**
 * @requires extension mcrypt_create_iv()
 */
class EncryptTest extends TestCase
{
    protected $crypt;

    public function setUp()
    {

        if (version_compare(PHP_VERSION, '7.0.0') <= 0) {

            $this->markTestSkipped(
                'mcrypt_create_iv() function deprecated in PHP 7.1'
            );
        }

        $this->configure();
        $this->crypt = Encrypt::create();
    }

    private function configure()
    {
        $configuration = [
            'global.config' => [
                'encryption.key'  => 'cygnite-shaXatBNHQ434',
            ],
        ];

        Config::$config = $configuration;
    }

    /**
     * @requires PHP 7.0
     */
    public function testMakeInstance()
    {
        $crypt = new Encrypt('cygnite-shaXatBNHQ4YEJ32');
        $this->assertInstanceOf('Cygnite\Common\Encrypt', $crypt);
    }

    /**
     * @requires PHP 7.0
     */
    public function testSecureKey()
    {
        $this->assertNotNull($this->crypt->getKey());
        $this->assertSame(hash('sha256', 'cygnite-shaXatBNHQ434', true), $this->crypt->getKey());
    }

    /**
     * @requires PHP 7.0
     */
    public function testEncodeString()
    {
        $string = 'PHP secure string';
        $this->assertNotNull($this->crypt->encode($string));
        $this->assertEquals('SVPLBtkxh/xmCfoZpaP0WOn1a3AwhnaB5peaP+TdIP0=', $this->crypt->encode($string));
    }

    /**
     * @requires PHP 7.0
     */
    public function testDecodeString()
    {
        $string = 'SVPLBtkxh/xmCfoZpaP0WOn1a3AwhnaB5peaP+TdIP0=';
        $this->assertEquals('PHP secure string', $this->crypt->decode($string));
    }
}
