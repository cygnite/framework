<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Database\Configure;

class ActiveRecordTest extends TestCase
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

    public function testAttributesSetsIntoActiveRecordObject()
    {
        $stub = new \Cygnite\Tests\Database\Cyrus\ModelsStub\User();
        $stub->first_name = 'Sanjoy';
        $stub->last_name = 'Dey';
        $stub->country = 'India';

        $this->assertArrayHasKey('first_name', $stub->getAttributes());
        $this->assertArrayHasKey('last_name', $stub->getAttributes());
        $this->assertArrayHasKey('country', $stub->getAttributes());
        $this->assertEquals('India', $stub->country);
        $this->assertEquals(['first_name' => 'Sanjoy', 'last_name' => 'Dey', 'country' => 'India'], $stub->getAttributes());
    }

    /**
     * @expectedException  \LogicException
     */
    public function testReturnsObjectById()
    {
        $user = \Cygnite\Tests\Database\Cyrus\ModelsStub\User::find(1);
    }

    //public function test
}
