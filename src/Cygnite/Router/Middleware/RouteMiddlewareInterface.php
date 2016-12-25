<?php
namespace Cygnite\Router\Middleware;

use Closure;
use Cygnite\Http\Requests\Request;

/**
 * Defines the interface for route middleware to implement
 *
 * @package Cygnite\Router\Middleware
 */
interface MiddlewareInterface
{
    /**
     * This function is called before handling the request
     */
    /**
     * Handles a request
     *
     * @param Request $request The request to handle
     * @param callable|Closure $next The next middleware item
     * @return Response The response after the middleware was run
     */
    public function handle(Request $request, Closure $next);
}
