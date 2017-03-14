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

use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Container\Service\ServiceProvider;

/**
 * Class PipelineServiceProvider.
 */
class PipelineServiceProvider extends ServiceProvider
{
    protected $container;

    /**
     * Register Pipeline into application container.
     *
     * @param ContainerAwareInterface $container
     */
    public function register(ContainerAwareInterface $container)
    {
        $container->set('pipeline', new Pipeline($container));
    }
}
