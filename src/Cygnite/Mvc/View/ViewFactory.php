<?php
namespace Cygnite\Mvc\View;

use Cygnite\Container\Container;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class ViewFactory
 *
 * @package Cygnite\Mvc\View
 */
class ViewFactory
{
    /**
     * Create view and set container object
     *
     * @param string $class
     * @param Container $container
     * @param callable $callback
     * @return mixed
     */
    public static function make(string $class, Container $container, callable $callback)
    {
        return $callback($container->make($class));
    }
}
