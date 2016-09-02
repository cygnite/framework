<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\AuthManager;

use Cygnite\DependencyInjection\ServiceProvider;
use Cygnite\Foundation\Application;

class AuthServiceProvider extends ServiceProvider
{
    protected $container;

    public function register(Application $app)
    {
        $app->singleton('auth', function ($c) {
            return new Auth($c['auth.provider']);
        });
    }
}
