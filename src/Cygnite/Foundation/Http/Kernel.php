<?php
namespace Cygnite\Foundation\Http;

use Throwable;
use Cygnite\Pipeline\Pipeline;
use Cygnite\Base\Router\Router;
use Cygnite\Foundation\Application;
use Cygnite\Http\Responses\Response;
use Cygnite\Http\Responses\ResponseInterface;

/**
 * Class Kernel
 * @package Cygnite\Foundation\Http
 */
class Kernel implements KernelInterface
{
    /**
     * The application implementation.
     *
     * @var \Cygnite\Foundation\Application
     */
    protected $app;

    protected $middleware = [];

    protected $router;

    /**
     * @param $app
     * @param null $router
     */
    public function __construct($app, $router = null)
    {
        $this->app = $app;
        if (!is_null($router)) {
            $this->router = $router;
        }
    }

    /**
     * @param $router
     * @param $request
     */
    public function setRouter($router, $request)
    {
        $this->router = $router;
        $this->app->set('router', $router);
        $this->app->set('request', $request);
    }

    /**
     * @param $request
     * @return array|ResponseInterface|mixed|static
     * @throws Exception|\Exception
     *
     * @note this function is incomplete, need to enhance
     * for better exception handling
     */
    public function handle($request)
    {
        $this->setRouter($this->app->compose('Cygnite\Base\Router\Router', $request), $request);

        try {
            $response = $this->sendRequestThroughRouter($request);
            /**
             * Check whether return value is a instance of Response,
             * else we will try to fetch the response form container,
             * create a response and return to the browser.
             *
             */
            if (!$response instanceof ResponseInterface && !is_array($response)) {
               $r = $this->app->has('response') ? $this->app->get('response') : '';
               $response = Response::make($r);
            }
        } catch (Exception $e) {
            if (ENV == 'development') {
                throw $e;
            }

            if (ENV == 'production') {

                /**
                 * We will log exception if logger enabled
                 */
                if ($this->app['debugger']->isLoggerEnabled()) {
                    $this->app['debugger']->log($e);
                }

                $this->app['debugger']->renderErrorPage($e);
            }
        } catch (Throwable $e) {
        }

        return $response;
    }

    protected function reportException(Exception $e)
    {
        $this->app['debugger']->report($e);
    }


    protected function renderException($request, Exception $e)
    {
        return $this->app['debugger']->render($request, $e);
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->bootApplication($request);

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->parseMiddlewareToPipelines($this->getMiddleware()), 'handle')
            ->then($this->dispatchToRouter())
            ->run();
    }

    /**
     * @return array
     * @note Middleware array validation needed
     * Need to be enhanced
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param array $middlewares
     * @return array
     */
    protected function parseMiddlewareToPipelines(array $middlewares)
    {
        $pipes = [];
        // check all types and store value into pipes array
        foreach ($middlewares as $middleware) {
            if (is_object($middleware)) {
                $pipes[] = $middleware;
            } else {
                $pipes[] = $this->app->make($middleware);
            }
        }

        return $pipes;
    }

    /**
     * @return callable
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            return $this->router->setApplication($this->app)->dispatch($request);
        };
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function addMiddleware($middleware)
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function terminate($request, $response)
    {

    }
}