<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Services\Omnipay\Providers;

use Cygnite\Helpers\Config;
use Omnipay\Common\GatewayFactory;
use Cygnite\Foundation\Application;
use Cygnite\Services\Omnipay\GatewayManager;
use Cygnite\Container\Service\ServiceProvider;

/**
 * Class OmnipayServiceProvider
 *
 * @package Cygnite\Services\Omnipay\Providers
 */
class OmnipayServiceProvider extends ServiceProvider
{
    protected $app;

    private $config;

    public function register(Application $app)
    {
        $this->configureOmnipay();
        $this->registerOmnipay();
    }

    /**
     * Register the Stripe API class.
     *
     * @return void
     */
    protected function registerOmnipay()
    {
        $this->app->singleton('omnipay', function ($c) {
            $omnipay = new GatewayManager($this->app, new GatewayFactory(), $this->config);
            $omnipay->gateway();

            return $omnipay;
        });
    }

    /**
     * Set Stripe Configuration
     *
     * @return void
     */
    protected function configureOmnipay()
    {
        $this->config = Config::get('global.config', 'omnipay.config');
    }
}
