<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Pipeline;

use Cygnite\Container\Service\ServiceProvider;
use Cygnite\Foundation\Application;

/**
 * Class PipelineServiceProvider.
 */
class PipelineServiceProvider extends ServiceProvider
{
    protected $app;

    /**
     * Register Pipeline into application container.
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['pipeline'] = new Pipeline($app);
    }
}
