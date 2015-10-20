<?php
use Mockery as m;
use Cygnite\Helpers\Config;
use Cygnite\Common\Mail\Mailer;

class MailerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $configuration = [
            'global.config' => [
                'email.configurations' => [

                    'protocol' => 'smtp',

                    'smtp' => [
                        'host' => 'smtp.gmail.com',
                        'username' => 'sanjoyinfotech@gmail.com',
                        'password' => 'lovemesanjoy',
                        'port' => '465',
                        'encryption' => 'ssl',
                    ],
                    'sendmail' => [
                        'path' => '/usr/sbin/exim -bs'
                    ],

                ],

            ]
        ];

        Config::$config = $configuration;
    }

    public function testMailerClosureInstance()
    {
        list($mailer, $message) = Mailer::compose(function ($mailer, $message)
        {
            return [$mailer, $message];
        });

        $this->assertInstanceOf('\Cygnite\Common\Mail\Mailer', $mailer);
        $this->assertInstanceOf('\Swift_Message', $mailer->message());
    }

    public function testFailureTest()
    {
        $transport = m::mock('Swift_Transport');
        $mailer = new Mailer(m::mock('Swift_Mailer'));
        $mailer->getSwiftMailer()->shouldReceive('getTransport')->andReturn($transport);
        $transport->shouldReceive('stop');

        $swift = new TestFailureTransportStub();
        $mailer->setSwiftMailer($swift);
        $mailer->send($mailer->message());
        $this->assertEquals(['dey.sanjoy0@gmail.com'], $mailer->failedRecipients());
    }

    public function testSetMessageContentProperlyIntoMessageObject()
    {
        $transport = m::mock('Swift_Transport');
        $message = m::mock('Swift_Message[getBody]');
        $mailer = $this->getMock('Cygnite\Common\Mail\Mailer', ['message'], [$message]);
        $mailer->getSwiftMailer()->shouldReceive('getTransport')->andReturn($transport);
        $transport->shouldReceive('stop');
        //$mailer->expects($this->once())->method('message')->will($this->returnValue($message));
        //$swiftMessage = $mailer->message();

        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('getBody')->andReturn("rendered.view");
        $message->shouldReceive('setFrom')->never();

        $this->assertEquals("rendered.view", $message->getBody());
    }

    private function createTransport()
    {
        return m::mock('Swift_Transport')->shouldIgnoreMissing();
    }
    private function createMessage()
    {
        return m::mock('Swift_Mime_Message')->shouldIgnoreMissing();
    }

    private function createMailer(Swift_Transport $transport)
    {
        return new Swift_Mailer($transport);
    }
}

class TestFailureTransportStub
{
    public function send($message, &$failed)
    {
        $failed[] = 'dey.sanjoy0@gmail.com';
    }
    public function getTransport()
    {
        $transport = m::mock('Swift_Transport');
        $transport->shouldReceive('stop');

        return $transport;
    }
}
