<?php

namespace Cygnite\Router;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

interface RouterInterface
{
    /**
     * Before routing filter must implement in the class.
     *
     * @param $methods
     * @param $pattern
     * @param $fn
     *
     * @return mixed
     */
    public function before($methods, $pattern, $fn);

    /**
     * Handle Router GET request.
     *
     * @param $pattern
     * @param $fn
     *
     * @return mixed
     */
    public function get(string $pattern, $fn);

    /**
     * Handle Router POST request, must implement in the class.
     *
     * @param $pattern
     * @param $fn
     *
     * @return mixed
     */
    public function post(string $pattern, $fn);

    /**
     * Handle PUT|PATCH request, must implement in the class.
     *
     * @param $pattern
     * @param $fn
     *
     * @return mixed
     */
    public function put(string $pattern, $fn);

    /**
     * Shorthand for route accessed using patch.
     *
     * @param $pattern
     * @param $func
     *
     * @return bool
     */
    public function patch(string $pattern, $func);

    /**
     * Handle DELETE request, must implement in the class.
     *
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function delete(string $pattern, $fn);

    /**
     * @param $pattern
     * @param $fn
     * @return mixed
     */
    public function options(string $pattern, $fn);

    /**
     * This method respond to any HTTP method.
     *
     * @param $pattern
     * @param $func
     * @return bool
     */
    public function any(string $pattern, $func);

    /**
     * Allow you to apply nested sub routing.
     *
     * @param          $groupRoute
     * @param callable $callback
     */
    public function group($groupRoute, \Closure $callback);

    /**
     * Set the controller as Resource Controller
     * Router knows how to respond to resource controller
     * request automatically.
     *
     * @param $name
     * @param $controller
     *
     * @return $this
     */
    public function resource(string $name, string $controller);

    /**
     * Customize the routing pattern using where.
     *
     * @param $key
     * @param $pattern
     *
     * @return $this
     */
    public function where($key, $pattern);

    /**
     * @param $fn
     * @return mixed
     */
    public function set404Page(callable $fn);

    /**
     * Execute after filter after all request processed,
     * We will implement it into Router class.
     *
     * @param $func
     * @return mixed
     */
    public function after(callable $func);

    /**
     * @param null $callback
     * @return mixed
     */
    public function run($callback = null);
}
