<?php

namespace Cygnite\Foundation\Http;

use Closure;
use Throwable;
use Cygnite\Pipeline\Pipeline;
use Cygnite\Base\Router\Router;
use Cygnite\Http\Responses\Response;
use Cygnite\Http\Requests\RequestInterface;
use Cygnite\Foundation\ApplicationInterface;
use Cygnite\Http\Responses\ResponseInterface;
use Cygnite\Container\ContainerAwareInterface;

/**
 * Class Kernel.
 */
class Kernel implements KernelInterface
{
    /**
     * The application implementation.
     *
     * @var \Cygnite\Foundation\Application
     */
    protected $app;

    /**
     * @var \Cygnite\Container\ContainerInterface
     */
    protected $container;

    protected $middleware = [];

    protected $router;

    protected $pipeline;

    protected $exceptionHandler;

    /**
     * Constructor to set Application instance.
     *
     * @param $app ApplicationInterface
     * @param null $router
     */
    public function __construct(ApplicationInterface $app, $router = null)
    {
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->exceptionHandler = $this->container['debugger'];
        if (!is_null($router)) {
            $this->router = $router;
        }
    }

    /**
     * Set Router and Request instance into Container
     *
     * @param $router
     * @param $request
     */
    public function setRouter($router, $request)
    {
        $this->router = $router;
        $this->container->set('router', $router);
        $this->container->set('request', $request);
    }

    /**
     * Handle the request and dispatch to routes.
     *
     * @param $request
     * @throws Exception|\Exception
     * @return array|ResponseInterface|mixed|static
     */
    public function handle($request) : ResponseInterface
    {
        $this->setRouter($this->container->makeInstance(\Cygnite\Base\Router\Router::class, $request), $request);

        try {
            $response = $this->sendRequestThroughRouter($request);
            /*
             * Check whether return value is a instance of Response,
             * otherwise we will try to fetch the response form container,
             * create a response and return to the browser.
             *
             */
            if (!$response instanceof ResponseInterface && !is_array($response)) {
                $r = $this->container->has('response') ? $this->container->get('response') : '';
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
     * Handle Exception & errors.
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
     * Report and throw exception.
     *
     * @param $e
     */
    protected function reportException($e)
    {
        return $this->exceptionHandler->report($e);
    }

    /**
     * @param \Exception $e
     *
     * @return mixed
     */
    protected function renderException(\Exception $e)
    {
        return $this->exceptionHandler->render($e);
    }

    /**
     * Prepare pipeline requests and send through router.
     *
     * @param $request
     *
     * @return mixed
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->bootApplication($request);
        $this->pipeline = new Pipeline($this->container);

        return $this->pipeline
            ->send($request)
            ->through($this->parseMiddlewareToPipelines($this->getMiddleware()), 'handle')
            ->then($this->dispatchToRouter())
            ->run();
    }

    /**
     * Return Middleware array stack.
     *
     * @return array
     * @note Middleware array validation needed
     * Need to be enhanced
     */
    public function getMiddleware() : array
    {
        return $this->middleware;
    }

    public function registerBootstrappers()
    {

    }

    /**
     * Parse Middlewares to pipelines
     *
     * @param array $middlewares
     * @return array
     */
    protected function parseMiddlewareToPipelines(array $middlewares) : array
    {
        $pipes = [];
        // check all types and store value into pipes array
        foreach ($middlewares as $middleware) {
            if (is_object($middleware)) {
                $pipes[] = $middleware;
            } elseif (string_has($middleware, ':')) {
                $pipes[] = $middleware;
            } else {
                $pipes[] = $this->container->make($middleware);
            }
        }

        return $pipes;
    }

    /**
     * Application booted, let's dispatch the routes.
     *
     * @return callable
     */
    protected function dispatchToRouter() : Closure
    {
        return function ($request) {
            return $this->router->setContainer($this->container)->dispatch($request);
        };
    }

    /**
     * Add middlewares to HTTP application.
     *
     * @param $middleware
     *
     * @return $this
     */
    public function addMiddleware($middleware) : KernelInterface
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public function getApplication() : ContainerAwareInterface
    {
        return $this->container;
    }

    /**
     * Fire shutdown method of middleware class.
     *
     * @param $request
     * @param $response
     */
    public function shutdown($request, $response)
    {
        $routeMiddlewares = $this->router->getRouteMiddlewares();

        $middlewares = array_merge(array_filter([$routeMiddlewares]), $this->middleware);

        foreach ($middlewares as $middleware) {
            list($name, $parameters) = $this->pipeline->parsePipeString($middleware);

            $instance = $this->container->make($name);

            if (method_exists($instance, 'shutdown')) {
                $instance->shutdown($request, $response);
            }
        }
    }
}
