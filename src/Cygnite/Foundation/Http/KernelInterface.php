<?php

namespace Cygnite\Foundation\Http;

interface KernelInterface
{
    /**
     * Handle the request and dispatch to routes.
     *
     * @param $request
     *
     * @throws Exception|\Exception
     *
     * @return array|ResponseInterface|mixed|static
     *
     *
     * @note this function is incomplete, need to enhance
     * for better exception handling
     */
    public function handle($request);

    /**
     * Add middlewares to HTTP application.
     *
     * @param $middleware
     *
     * @return $this
     */
    public function addMiddleware($middleware);

    public function getMiddleware();

    /**
     * Get the container instance.
     *
     * @return Container
     */
    public function getContainer();

    /**
     * Fire shutdown method of middleware class.
     *
     * @param $request
     * @param $response
     */
    public function shutdown($request, $response);
}
