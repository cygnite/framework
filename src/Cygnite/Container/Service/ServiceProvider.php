<?php
namespace Cygnite\Container\Service;

use Cygnite\Foundation\Application;

/**
 * Class Container
 *
 * @package Cygnite\Container\Service
 * @author  Sanjoy Dey
 */

abstract class ServiceProvider
{
    protected $app;

    /**
     * Create a new service provider instance.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the service provider.
     *
     * @param \Cygnite\Foundation\Application $app
     * @return void
     */
    abstract public function register(Application $app);
}
