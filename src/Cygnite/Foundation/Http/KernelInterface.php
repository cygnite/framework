<?php
namespace Cygnite\Foundation\Http;

interface KernelInterface
{
    /**
     * Handle the request and dispatch to routes
     *
     * @param $request
     * @return array|ResponseInterface|mixed|static
     * @throws Exception|\Exception
     *
     * @note this function is incomplete, need to enhance
     * for better exception handling
     */
    public function handle($request);

    /**
     * Add middlewares to HTTP application
     *
     * @param $middleware
     * @return $this
     */
    public function addMiddleware($middleware);

    public function getMiddleware();

    /**
     * Get the application instance
     *
     * @return Application
     */
    public function getApplication();

    /**
     * Fire shutdown method of middleware class
     *
     * @param $request
     * @param $response
     */
    public function shutdown($request, $response);
}
