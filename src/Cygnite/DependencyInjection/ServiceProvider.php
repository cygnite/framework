<?php
namespace Cygnite\DependencyInjection;

use Cygnite\Foundation\Application as App;

/**
 * Class Container
 *
 * @package Cygnite\DependencyInjection
 * @author  Sanjoy Dey
 */

abstract class ServiceProvider
{
    protected $container;

    /**
     * Create a new service provider instance.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->container = $app;
    }

    /**
     * Register the service provider.
     *
     * @param \Cygnite\Foundation\Application $app
     * @return void
     */
    abstract public function register(App $app);
}
