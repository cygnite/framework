<?php
namespace Cygnite\Base\Router;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

interface RouterInterface
{
    /**
     * Before routing filter must implement in the class
     * @param $methods
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function before($methods, $pattern, $fn);

    /**
     * Handle Router GET request
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function get($pattern, $fn);

    /**
     * Handle Router POST request, must implement in the class
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function post($pattern, $fn);

    /**
     * Handle PUT|PATCH request, must implement in the class
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function put($pattern, $fn);

    /**
     * Handle DELETE request, must implement in the class
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function delete($pattern, $fn);

    /**
     *
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function options($pattern, $fn);

    /**
     * @param $fn
     * @return mixed
     */
    public function set404($fn);

    /**
     * Execute after filter after all request processed,
     * We will implement it into Router class
     * @param $func
     * @return mixed
     */
    public function after($func);

    /**
     * @param null $callback
     * @return mixed
     */
    public function run($callback = null);
}