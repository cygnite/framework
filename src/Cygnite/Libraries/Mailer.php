<?php
namespace Cygnite\Libraries;

use Closure;
use Exception;
use Swift_Image;
use Swift_Mailer as Email;
use Cygnite\Helpers\Config;
use Swift_Message as MailMessage;
use Swift_Attachment as MailAttachment;
use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Swift_SendmailTransport as SendmailTransport;

if ( ! defined('CF_SYSTEM')) exit('External script access not allowed');

include '/../../../../swiftmailer/swiftmailer/lib/swift_required.php';
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3  or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                         :  Packages
 * @Sub Packages                    :  Library
 * @Filename                        :  Email
 * @Description                     :  This library will be available with all features in next version.
 * @Author                          :  Cygnite Dev Team
 * @Copyright                       :  Copyright (c) 2013 - 2014,
 * @Link	                        :  http://www.cygniteframework.com
 * @Since	                        :  Version 1.0
 * @Filesource                      :
 * @Warning                         :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *  Mailer::instance(function($mailer) {
 *
 *      $mailer->setDriver('SMTP');
 *      $mailer->setHost('smtp.example.org');
 *      $mailer->setPort('25');
 *     //$mailer->setEncryption('25');
 *      $mailer->setCredentials(array(
                                    'username' => 'Your Username',
 *                                  'password' => 'Your Password'
 *
 *      ));
 *
 *
 *     $mailMessage = $mailer->setPriority($priority)
                ->setSubject($subject)
                ->setFrom(array($from_email => $from_name))
                ->setTo(array($to_email => $to_name))
                ->setReadReceiptTo(SYS_EMAIL)
                ->setBody('Here is the message itself')
 *              ->setAttach()
                ->addPart('<q>Here is the message itself</q>', 'text/html')
 *              ->getMessage();
 *
 *
 *
 *      $mailer->send($mailMessage);
 * });
 *
 *
 *
 */

class Mailer
{

    private $emailConfig = array();

    private $transportInstance;

    public function post($request_data=NULL)
    {
        $transport = Swift_SmtpTransport::newInstance()
            ->setHost('host')
            ->setPort('port')
            ->setUsername('username')
            ->setPassword('password');

        $mailer = Swift_Mailer::newInstance($transport);
        $message = Swift_Message::newInstance()
            ->setPriority($priority)
            ->setSubject($subject)
            ->setFrom(array($from_email => $from_name))
            ->setTo(array($to_email => $to_name))
            ->setReadReceiptTo(SYS_EMAIL)
            ->setBody('Here is the message itself')
            ->addPart('<q>Here is the message itself</q>', 'text/html');
        ;
        $result = $mailer->send($message);
    }


    public function __construct()
    {
        $this->emailConfig = Config::get('global_config', 'email_configurations');
        $this->setTransportType($this->emailConfig['protocol']);
    }



    public static function __callStatic($method, $arguments)
    {
        if ($method == 'instance') {
            return call_user_func_array(array(new self,'get'.ucfirst($method)), $arguments);
        }
    }

    public function getInstance(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new self);
        }

        return new self;
    }

    private function setTransportType($type)
    {
        $type = ucfirst($type);

        switch ($type) {
            case 'Mail':
                $this->setMailTransport();
                break;
            case 'Smtp':
                $this->setSmtpTransport();
                break;
            case 'Sendmail':
                $this->setSendMailTransport();
                break;
        }

    }

    private function setConfig($swift, $attributes)
    {
        foreach ($attributes as $key => $value) {
            echo $method = 'set'.ucfirst($key); echo "<br>  ";
            $swift->{$method}($value);
        }

    }

    private function setSmtpTransport()
    {
        var_dump($this->emailConfig['smtp']);

        $this->transportInstance = SmtpTransport::newInstance();

        $this->setConfig($this->transportInstance, $this->emailConfig['smtp']);
    }

    private function setSendMailTransport()
    {
        SendmailTransport::newInstance();

    }

    private function setMailTransport()
    {

        MailTransport::newInstance();
    }

    public function getTransportInstance($type = 'smtp')
    {
        if ($type == 'smtp') {
            return SmtpTransport::newInstance();
        } else if ($type == 'sendmail') {
            return SendmailTransport::newInstance();
        } else {
            return MailTransport::newInstance();
        }
    }

    public function getMessageInstance()
    {
        return MailMessage::newInstance();
    }

    public function send($message)
    {
        $mailer = null;
        if ($this->transportInstance instanceof SmtpTransport) {
            $mailer = Email::newInstance($this->transportInstance);
        }

        try {
            if ($message instanceof MailMessage) {
                return $mailer->send($message);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }

    }

    public function addAttachment($path)
    {
        return MailAttachment::fromPath($path);

    }

}
