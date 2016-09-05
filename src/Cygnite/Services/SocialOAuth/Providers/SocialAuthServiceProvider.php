<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Services\SocialOAuth\Providers;

use Cygnite\Container\Service\ServiceProvider;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Session;

/**
 * Class SocialAuthServiceProvider.
 */
class SocialAuthServiceProvider extends ServiceProvider
{
    protected $app;

    private $config;

    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        if ($this->configureSocialAuth()) {
            $this->registerSocialAuth();
        }
    }

    /**
     * Register the Social OAuth API class.
     *
     * @return void
     */
    protected function registerSocialAuth()
    {
        // Session storage
        $storage = new Session();
        $factory = new \OAuth\ServiceFactory();

        foreach ($this->config['active'] as $key => $social) {
            $this->app->singleton($social, function ($c) use ($social, $factory, $storage) {
                // Setup the credentials for the requests
                $credentials = new Credentials(
                    $this->config[$social]['key'],
                    $this->config[$social]['secret'],
                    $this->app->request->getFullUrl()
                );

                return $factory->createService($social, $credentials, $storage, []);
            });
        }
    }

    /**
     * Set Social OAuth Configuration.
     *
     * @return void
     */
    protected function configureSocialAuth()
    {
        $this->config = Config::get('global.config', 'social.config');

        if (isset($this->config['active']) && !empty($this->config['active'])) {
            return true;
        }

        return false;
    }
}
