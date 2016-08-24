<?php

namespace Cygnite\Common\Mail;

use Cygnite\Container\Service\ServiceProvider;
use Cygnite\Foundation\Application;

class MailServiceProvider extends ServiceProvider
{
    protected $app;

    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app->singleton('mailer', function () {
            $mailer = new \Cygnite\Common\Mail\Mailer();
            $mailer->setSwiftMailer(new \Swift_Mailer($mailer->getTransportInstance()));

            return $mailer;
        });
    }
}
