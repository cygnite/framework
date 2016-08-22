<?php
namespace Cygnite\Common\Mail;

interface MailerInterface
{
    /**
     * Get the instance of the Mailer
     *
     * @param null $callback
     * @return static
     */
    public static function compose($callback = null);

    /**
     * Get Smtp transport instance
     *
     * @return mixed
     */
    public function getSmtpTransport();

    /**
     * Get sendmail transport instance
     *
     * @return mixed
     */
    public function getSendMailTransport();

    /**
     * Get mail transport instance
     *
     * @return mixed
     */
    public function getMailTransport();

     /**
     * Get Transport instance (object). By default it will return smtp instance
     *
     * @access public
     * @param  $type string
     * @return object
     *
     */
    public function transport($type = 'smtp');
     /**
     * Get Message Instance
     *
     * @access public
     * @param  null
     * @return object of MailMessage
     *
     */
    public function message();

    /**
     * Send email with message
     *
     * @access public
     * @param  $message your email contents
     * @throws \Exception
     * @return unknown
     */
    public function send($message);

    /**
     * Add attachment to your email
     *
     * @access public
     * @param  $path path of your email attachment
     * @return unknown
     *
     */
    public function attach($path);
}
