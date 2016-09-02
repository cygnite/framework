<?php

namespace Cygnite\Common\Mail;

use Closure;
use Cygnite\Helpers\Config;
use Swift_Attachment as MailAttachment;
use Swift_MailTransport as MailTransport;
use Swift_Message as MailMessage;
use Swift_SendmailTransport as SendmailTransport;
use Swift_SmtpTransport as SmtpTransport;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Mailer.
 *
 * Mailer is an Swiftmailer wrapper class to send email messages
 * simple and cleaner way.
 *
 *  Mailer::compose(function($mailer, $message)
 *  {
 *     $message->setPriority($priority)
 *             ->setSubject($subject)
 *             ->setFrom(array($from_email => $from_name))
 *             ->setTo(array($to_email => $to_name))
 *             ->setReadReceiptTo(SYS_EMAIL)
 *             ->setBody('Here is the message itself')
 *             ->setAttach()
 *             ->addPart('<p>Here is the message itself</p>', 'text/html')
 *             ->getMessage();
 *
 *      $mailer->send($message);
 * });
 */
class Mailer implements MailerInterface
{
    protected $swift;

    protected $transport;

    // email configuration
    private $emailConfig = [];

    private $smtpTransport;

    protected $sendMailTransport;

    protected $mailTransport;

    protected $mailMessage;

    protected $failedRecipients = [];

    /**
     * Constructor of Mailer.
     *
     * Configure Mailer to send email
     *
     * @param null $swift
     */
    public function __construct($swift = null)
    {
        if (!is_null($swift)) {
            $this->swift = $swift;
        }

        $this->emailConfig = Config::get('global.config', 'email.configurations');

        //set transport type protocol
        $this->setTransportType($this->emailConfig['protocol']);
    }

    /**
     * Get the instance of the Mailer.
     *
     * @param null $callback
     *
     * @return static
     */
    public static function compose($callback = null)
    {
        $mailer = new static();
        $mailer->setSwiftMailer(new \Swift_Mailer($mailer->getTransportInstance()));

        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback($mailer, $mailer->message());
        }

        return $mailer;
    }

    /**
     * Set Transport Type Mail/Smtp/Sendmail.
     *
     * @param  $type
     *
     * @return void
     */
    protected function setTransportType($type)
    {
        $type = ucfirst($type);

        switch ($type) {
            case 'Mail':
                $this->createMailTransport();
                $transport = $this->getMailTransport();
                break;
            case 'Smtp':
                $this->createSmtpTransport();
                $transport = $this->getSmtpTransport();
                break;
            case 'Sendmail':
                $this->createSendMailTransport();
                $transport = $this->getSendMailTransport();
                break;
        }

        $this->transport = $transport;
    }

    /**
     * @return mixed
     */
    public function getTransportInstance()
    {
        return $this->transport;
    }

    /**
     * Set Email configurations dynamically to SwiftMailer.
     *
     * @param $swiftSmtpTransport
     * @param $attributes
     */
    public function setSmtpConfig($swiftSmtpTransport, $attributes)
    {
        foreach ($attributes as $key => $value) {
            $method = 'set'.ucfirst($key);
            $swiftSmtpTransport->{$method}($value);
        }
    }

    /**
     * Set SMTP transport.
     *
     * @param  null
     *
     * @return void
     */
    protected function createSmtpTransport()
    {
        $this->smtpTransport = SmtpTransport::newInstance();

        $this->setSmtpConfig($this->smtpTransport, $this->emailConfig['smtp']);
    }

    /**
     * Get Smtp transport instance.
     *
     * @return mixed
     */
    public function getSmtpTransport()
    {
        return $this->smtpTransport;
    }

    /**
     * Set SendMail transport.
     *
     * @param  null
     *
     * @return void
     */
    protected function createSendMailTransport()
    {
        $this->sendMailTransport = SendmailTransport::newInstance($this->emailConfig['sendmail']['path']);

        return $this;
    }

    /**
     * Get sendmail transport instance.
     *
     * @return mixed
     */
    public function getSendMailTransport()
    {
        return $this->sendMailTransport;
    }

    /**
     * Set Mail transport.
     *
     * @param  null
     *
     * @return void
     */
    protected function createMailTransport()
    {
        $this->mailTransport = MailTransport::newInstance();

        return $this;
    }

    /**
     * Get mail transport instance.
     *
     * @return mixed
     */
    public function getMailTransport()
    {
        return $this->mailTransport;
    }

    /**
     * Get Transport instance (object). By default it will return smtp instance.
     *
     * @param  $type string
     *
     * @return object
     */
    public function transport($type = 'smtp')
    {
        if ($type == 'smtp') {
            return $this->getSmtpTransport();
        } elseif ($type == 'sendmail') {
            return $this->getSendMailTransport();
        }

        return $this->getMailTransport();
    }

    /**
     * Get Message Instance.
     *
     * @param  null
     *
     * @return object of MailMessage
     */
    public function message()
    {
        return $this->mailMessage = MailMessage::newInstance();
    }

    /**
     * Send email with message.
     *
     * @param your $message
     *
     * @throws \InvalidArgumentException
     *
     * @return unknown
     */
    public function send($message)
    {
        if (!$message instanceof MailMessage) {
            throw new \InvalidArgumentException(
                sprintf('Mailer::%s expect instance of Swift_Message.', __FUNCTION__)
            );
        }

        return $this->swift->send($message, $this->failedRecipients);
    }

    /**
     * Return failed recipients.
     *
     * @return array
     */
    public function failedRecipients()
    {
        return $this->failedRecipients;
    }

    /**
     * Add attachment to your email.
     *
     * @param  $path path of your email attachment
     *
     * @return unknown
     */
    public function attach($path)
    {
        return MailAttachment::fromPath($path);
    }

    /**
     * Set SwiftMailer instance.
     *
     * @param $swift
     */
    public function setSwiftMailer($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Return SwiftMailer Instance.
     *
     * @return object
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }
}
