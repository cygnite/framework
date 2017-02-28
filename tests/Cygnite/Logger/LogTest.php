<?php
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function testInstanceOfLog()
    {
        $monolog = new \Monolog\Logger('loger');
        $log = $this->getMockBuilder("\\Cygnite\\Logger\\Log")->setConstructorArgs([$monolog])->getMock();
        $this->assertInstanceOf('Cygnite\Logger\Log', $log);
    }

    public function testRotatingFileHandler()
    {
        $monolog = m::mock('Monolog\Logger');
        $log = new \Cygnite\Logger\Log($monolog);
        $fileHandler = m::type('Monolog\Handler\RotatingFileHandler');
        $monolog->shouldReceive('pushHandler')->once()->with($fileHandler);
        $log->dailyFileLog(__DIR__, 3);
    }

    public function testLogReturnsInfoMessage()
    {
        $monolog = new \Monolog\Logger('loger');
        $log = $this->getMockBuilder("\\Cygnite\\Logger\\Log")->setConstructorArgs([$monolog])->getMock();
        $log->info("Info Msg.");
    }
}
