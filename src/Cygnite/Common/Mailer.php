<?php
namespace Cygnite\Common;

use Closure;
use Exception;
use Swift_Image;
use Swift_Mailer as Email;
use Cygnite\Helpers\Config;
use Swift_Message as MailMessage;
use Cygnite\Foundation\Application;
use Swift_Attachment as MailAttachment;
use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Swift_SendmailTransport as SendmailTransport;

if ( ! defined('CF_SYSTEM')) exit('External script access not allowed');

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
 * @Description                     :  Swiftmailer wrapper class to handle email functionalities
 * @Author                          :  Sanjoy Dey
 * @Copyright                       :  Copyright (c) 2013 - 2014,
 * @Link                            :  http://www.cygniteframework.com
 * @Since                           :  Version 1.0.6
 * @Filesource                      :
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
 */

class Mailer
{

    // email configuration
    private $emailConfig = array();

    private $transportInstance;


    public function __construct()
    {
        $this->emailConfig = Config::get('global.config', 'emailConfiguration');

        try {
            Application::import('vendor'.DS.$this->emailConfig['swift_mailer_path']);
        }catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
        
        //set transport type protocol
        $this->setTransportType($this->emailConfig['protocol']);
    }

    /**
     * Get the instance of the Mailer dynamically
     *
     * @access   public
     * @param       $method string
     * @param array $arguments
     * @internal param array $arguments
     * @return object
     */
    public static function __callStatic($method, $arguments)
    {
        if ($method == 'instance') {
            return call_user_func_array(array(new self,'get'.ucfirst($method)), $arguments);
        }
    }

    /**
     * Get the instance of the Mailer by _callStatic
     * 
     * @access public 
     * @param  Closure $callback
     * @return object
     * 
     */
    public function getInstance(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new Mailer);
        }

        return new Mailer;
    }

    /**
     * Set Transport Type Mail/Smtp/Sendmail
     * 
     * @access public 
     * @param  $type
     * @return void
     * 
     */
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

    /**
     * Set Email configurations dynamically to SwiftMailer
     * 
     * @access public 
     * @param  $swift swift instance
     * @param  $attributes attributes
     * @return void
     * 
     */
    private function setConfig($swift, $attributes)
    {
        foreach ($attributes as $key => $value) {
            $method = 'set'.ucfirst($key);
            $swift->{$method}($value);
        }

    }
    
    /**
     * Set SMTP transport 
     * 
     * @access public 
     * @param  null
     * @return void
     * 
     */
    private function setSmtpTransport()
    {
        $this->transportInstance = SmtpTransport::newInstance();

        $this->setConfig($this->transportInstance, $this->emailConfig['smtp']);
    }
    
     /**
     * Set SendMail transport 
     * 
     * @access public 
     * @param  null
     * @return void
     * 
     */
    private function setSendMailTransport()
    {
        SendmailTransport::newInstance();

    }
    
    /**
     * Set Mail transport 
     * 
     * @access public 
     * @param  null
     * @return void
     * 
     */
    private function setMailTransport()
    {

        MailTransport::newInstance();
    }

     /**
     * Get Transport instance (object). By default it will return smtp instance
     * 
     * @access public 
     * @param  $type string
     * @return object
     * 
     */
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

     /**
     * Get Message Instance 
     * 
     * @access public 
     * @param  null
     * @return object of MailMessage
     * 
     */
    public function getMessageInstance()
    {
        return MailMessage::newInstance();
    }

    /**
     * Send email with message
     *
     * @access public
     * @param  $message your email contents
     * @throws \Exception
     * @return unknown
     */
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

    /**
     * Add attachment to your email 
     * 
     * @access public 
     * @param  $path path of your email attachment
     * @return unknown
     * 
     */
    public function addAttachment($path)
    {
        return MailAttachment::fromPath($path);

    }
    
    /*
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
    }*/

}
