<?php

use Cygnite\Database\Configure;

class ConnectionTest extends PHPUnit_Framework_TestCase
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
        $connector = $this->getMock('Cygnite\Database\Connections\Mysql', ['create'], [Configure::$config['db']]);
        $connector->expects($this->once())->method('create')->will($this->returnValue('PDO'));

        $this->assertInstanceOf('Cygnite\Database\Connections\Mysql', $connector);
        $this->assertEquals('PDO', $connector->create());
    }
}
