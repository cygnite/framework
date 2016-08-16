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

    private $pipeline;

    protected $exceptionHandler;

    /**
     * @param $app
     * @param null $router
     */
    public function __construct($app, $router = null)
    {
        $this->app = $app;
        $this->exceptionHandler = $app['debugger'];
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
     * Handle the request and dispatch to routes
     *
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
             * otherwise we will try to fetch the response form container,
             * create a response and return to the browser.
             *
             */
            if (!$response instanceof ResponseInterface && !is_array($response)) {
               $r = $this->app->has('response') ? $this->app->get('response') : '';
               $response = Response::make($r);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (Throwable $e) {
            $this->handleException($e);
        }

        return $response;
    }

                /**
     * Handle Exception & errors
     *
     * @param $e
                 */
    protected function handleException($e)
    {
        switch (ENV) {
            case 'production':
                $this->renderException($e);
                break;
            default:
                $this->reportException($e);
                break;

            }
        }

    /**
     * Report and throw exception
     *
     * @param $e
     */
    protected function reportException($e)
    {
        return $this->exceptionHandler->report($e);
    }

    /**
     * @param \Exception $e
     * @return mixed
     */
    protected function renderException(\Exception $e)
    {
        return $this->exceptionHandler->render($e);
    }

    /**
     * Prepare pipeline requests and send through router
     *
     * @param $request
     * @return mixed
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->bootApplication($request);
        $this->pipeline = new Pipeline($this->app);

        return $this->pipeline
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
            } elseif (string_has($middleware, ':')){
                $pipes[] = $middleware;
            } else {
                $pipes[] = $this->app->make($middleware);
            }
        }

        return $pipes;
    }

    /**
     * Application booted, let's dispatch the routes
     *
     * @return callable
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            return $this->router->setApplication($this->app)->dispatch($request);
        };
    }

    /**
     * Add middlewares to HTTP application
     *
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

    /**
     * Get the application instance
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Fire shutdown method of middleware class
     *
     * @param $request
     * @param $response
     */
    public function shutdown($request, $response)
    {
        // @todo Merge application and routes middleware
        //$middlewares =  ;
        $routeMiddlewares = $this->router->getRouteMiddlewares();

        $middlewares =  array_merge(array_filter([$routeMiddlewares]), $this->middleware);
        foreach ($this->middlewares as $middleware) {
            list($name, $parameters) = $this->pipeline->parsePipeString($middleware);

            $instance = $this->app->make($name);

            if (method_exists($instance, 'shutdown')) {
                $instance->shutdown($request, $response);
            }
        }
    }
}