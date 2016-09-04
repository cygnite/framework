<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Services\Stripe\Providers;

use Cygnite\Helpers\Config;
use Cartalyst\Stripe\Stripe;
use Cygnite\Foundation\Application;
use Cygnite\Container\Service\ServiceProvider;

/**
 * Class StripeServiceProvider
 *
 * @package Cygnite\Services\Stripe\Providers
 */
class StripeServiceProvider extends ServiceProvider
{
    protected $app;

    private $config;
    
    public function register(Application $app)
    {
        $this->configureStripe();
        $this->registerStripe();
    }

    /**
     * Register the Stripe API class.
     *
     * @return void
     */
    protected function registerStripe()
    {
        $this->app->singleton('stripe', function ($c) {
            return new Stripe($this->config['secret'], $this->config['version']);
        });
    }

    /**
     * Set Stripe Configuration
     *
     * @return void
     */
    protected function configureStripe()
    {
        $this->config = Config::get('global.config', 'stripe.config');
    }
}
