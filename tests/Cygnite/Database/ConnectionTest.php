<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Database\Configure;

class ConnectionTest extends TestCase
{
    public function setUp()
    {
        Configure::database(function ($config) {
            $config->default = 'db';
            $config->set([
                    'db' => [
                        'driver'    => 'mysql',
                        'host'      => 'localhostf',
                        'port'      => '',
                        'database'  => 'foo_bar',
                        'username'  => 'root',
                        'password'  => '',
                        'charset'   => 'utf8',
                        'collation' => 'utf8_unicode_ci',
                    ],
                ]);
        });
    }

    public function testMySqlPdoConnection()
    {
        $connector = $this->getMockBuilder('Cygnite\Database\Connections\MySql')->setConstructorArgs([Configure::$config['db']])->setMethods(['create'])->getMock();
        $connector->expects($this->once())->method('create')->will($this->returnValue('PDO'));

        $this->assertInstanceOf('Cygnite\Database\Connections\MySql', $connector);
        $this->assertEquals('PDO', $connector->create());
    }
}
