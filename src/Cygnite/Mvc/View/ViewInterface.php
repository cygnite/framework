<?php

namespace Cygnite\Mvc\View;

use Cygnite\Container\Container;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * interface ViewInterface.
 */
interface ViewInterface
{
    /**
     * @param       $view
     * @param array $params
     * @param bool  $return
     *
     * @return mixed
     */
    public function render(string $view, array $params = [], $return = false);

    /**
     * @param       $view
     * @param array $data
     *
     * @return mixed
     */
    public function create($view = null, array $data = []);

    /**
     * @param          $view
     * @param array    $data
     * @param callable $callback
     *
     * @return mixed
     */
    public function compose(string $view, array $data = [], \Closure $callback = null);

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function with(array $params = []);

    /**
     * Return view content.
     *
     * @return mixed
     */
    public function content();

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * @return mixed
     */
    public function all() : array;

    /**
     * @param $container
     *
     * @return mixed
     */
    public function setContainer(Container $container);

    /**
     * @return mixed
     */
    public function getContainer() : Container;
}
