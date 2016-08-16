<?php
namespace Cygnite\Base\Router\Middleware;

use Cygnite\Http\Requests\Request;

interface RouteMiddlewareInterface
{
	/**
	This function is called before handling the route request
	*/
	public function handle(Request $request, \Closure $next);
}