<?php
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cygnite\Console\Foundation\Application;
use Cygnite\Tests\Console\Commands\GreetCommand;

class ConsoleApplicationTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testConsoleApplicationInstance()
    {
        $this->assertInstanceOf('\Cygnite\Console\Foundation\Application', $this->getMockConsole());
    }

    public function testCraftConsoleApplicationReturnsCorrectNameAndVersion()
    {
        $app = $this->getMockConsole();
        $this->assertEquals('Cygnite Framework: Craft Console Application', $app->getName());
        $this->assertEquals('testing version', $app->getVersion());
    }

    protected function getMockConsole()
    {
        $container = m::mock('Cygnite\Container\Container');
        return $this->getMockBuilder('Cygnite\Console\Foundation\Application')
                        ->setMethods(['addCommandToParent'])
                        ->setConstructorArgs([$container, 'testing version'])
                        ->getMock();
    }
}

class ConsoleApplication extends Application
{
    protected $commandsStack = [];

    public function resetCommandStack()
    {
        $this->commandsStack = [];
    }
}
