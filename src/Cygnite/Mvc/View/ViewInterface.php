<?php
namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * interface ViewInterface
 *
 * @package Cygnite\Mvc\View
 */
interface ViewInterface
{
    /**
     * @param       $view
     * @param array $params
     * @param bool  $return
     * @return mixed
     */
    public function render($view, $params = [], $return = false);

    /**
     * @param       $view
     * @param array $data
     * @return mixed
     */
    public static function create($view = null, array $data = []);


    /**
     * @param          $view
     * @param array    $data
     * @param callable $callback
     * @return mixed
     */
    public static function compose($view, array $data = [], \Closure $callback = null);

    /**
     * @param array $params
     * @return mixed
     */
    public function with(array $params = []);

    /**
     * Return view content
     *
     * @return mixed
     */
    public function content();

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setData($key, $value);

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @param $container
     * @return mixed
     */
    public function setContainer($container);

    /**
     * @return mixed
     */
    public function getContainer();
}