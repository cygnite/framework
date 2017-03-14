<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Router;

use Cygnite\Helpers\Config;
use Cygnite\Pipeline\Pipeline;
use Cygnite\Http\Requests\Request;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Router\Controller\{ControllerController, RouteController, ResourceController};

/*
 * Cygnite Router
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

class Router implements RouterInterface
{
    const MODULE_DIR = 'Modules';
    /**
     * The current attributes being shared by routes.
     */
    public static $group;
    public static $moduleDir;
    public static $indexPage = 'index.php';
    /**
     * The wildcard patterns supported by the router.
     * @var array
     */
    public $patterns = [
        '{:num}' => '([0-9]+)',
        '{:id}' => '(\d+)',
        '{:name}' => '(\w+)',
        '{:string}' => '(\w+)',
        '{:any}' => '([a-zA-Z0-9\.\-_%]+)',
        '{:all}' => '(.*)',
        '{:module}' => '([a-zA-Z0-9_-]+)',
        '{:namespace}' => '([a-zA-Z0-9_-]+)',
        '{:year}' => '\d{4}',
        '{:month}' => '\d{2}',
        '{:day}' => '\d{2}(/[a-z0-9_-]+)',
    ];
    public $response;
    public $container;
    public $request;
    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = [];

    /**
     * @var array The before middle-ware route patterns and their handling functions
     */
    private $before = [];
    /**
     * @var object The function to be executed when no route has been matched
     */
    private $notFound;
    private $handledRoute;
    private $afterRouter;
    private $routeBasePath = '';
    private $after = [];
    protected $resourceController, $routeController;
    protected $middleware;

    /**
     * Router constructor.
     *
     * @param ResourceController $resourceController
     * @param RouteController $routeController
     */
    public function __construct(
        ResourceController $resourceController,
        RouteController $routeController
    ) {
        $this->resourceController = $resourceController;
        $this->routeController = $routeController;
    }

    /**
     * Set Http request.
     *
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) : Router
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set RouteCollection.
     *
     * @param $namespace
     * @throws InvalidRouterCollectionException
     * @return mixed
     */
    public function collection($namespace)
    {
        if (!class_exists($namespace)) {
            throw new InvalidRouterCollectionException('Route Collection Class $namespace doesn\'t exists');
        }

        $routeCollection = $this->getContainer()->make($namespace);

        return $routeCollection->setRouter($this);
    }

    /**
     * Set application instance.
     *
     * @param $container
     * @return $this
     */
    public function setContainer($container) : Router
    {
        $this->container = $container;
        $this->routeController->setContainer($container);
        $this->resourceController->setRouter($this);

        return $this;
    }

    /**
     * Get Container instance.
     *
     * @return ContainerAwareInterface
     */
    public function getContainer() : ContainerAwareInterface
    {
        return $this->container;
    }

    /**
     * Return route controller instance.
     *
     * @return RouteController
     */
    public function getRouteControllerObject()
    {
        return $this->routeController;
    }

    /**
     * Call controller action.
     *
     * @param $arguments
     * @return object
     */
    public function callController($arguments)
    {
        return $this->routeController->callController($arguments);
    }

    /**
     * Store a before middle-ware route and a handling function to be executed
     * when accessed using one of the specified methods.
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /home/system
     * @param object $func    The handling function to be executed
     *
     * @return mixed|void
     */
    public function before($methods, $pattern, $func)
    {
        $pattern = $this->setBaseRoute($pattern);

        foreach (explode('|', $methods) as $method) {
            $this->before[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }
    }

    /**
     * Set base path.
     *
     * @param $pattern
     * @return string
     */
    private function setBaseRoute($pattern)
    {
        $pattern = $this->routeBasePath . '/' . trim($pattern, '/');

        return $this->routeBasePath ? rtrim($pattern, '/') : $pattern;
    }

    /**
     * After routing event.
     *
     * @param $func
     * @return mixed|void
     */
    public function after(callable $func)
    {
        $pattern = $this->setBaseRoute('{:all}');
        foreach (explode('|', 'GET|POST|PUT|PATCH|DELETE') as $method) {
            $this->after[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }
    }

    /**
     * Sometime you may also want to change the 'modules' directory
     * name. Such cases set module directory name to be identified by Router.
     *
     * @param $name
     */
    public function setModuleDirectory($name)
    {
        static::$moduleDir = $name;
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string $pattern A route pattern such as /home/system
     * @param object $func The handling function to be executed
     * @return bool
     */
    public function get(string $pattern, $func)
    {
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        $method = strtoupper(__FUNCTION__);

        if (!$func instanceof \Closure) {
            return $this->override($pattern, $func, false, $method);
        }

        return $this->match($method, $pattern, $func);
    }

    /**
     * @param        $pattern
     * @param        $func
     * @param        $method
     * @param string $overrideWith
     *
     * @return bool
     */
    public function override($pattern, $func, $method, $overrideWith = 'GET')
    {
        /*
         * We will bind static routes to callable
         *
         * closure object
         * @return object
         */
        $callable = function () use ($func) {
            return $this->callStaticRoute($func);
        };

        $requestMethod = $this->request->server->get('REQUEST_METHOD');

        if ($method !== false) {
            if ($requestMethod == $method) {
                $overrideWith = $requestMethod;
            }
        }

        return $this->match($overrideWith, $pattern, $callable);
    }

    /**
     * Call static routes to controller.
     * @param $uri
     * @return object
     */
    public function callStaticRoute($uri)
    {
        $params = array_slice($this->getUrlSegments(), 2);

        return $this->callController([$uri, $params]);
    }

    /**
     * Store a route and a handling function to be executed.
     * Routes will execute when accessed using specific url pattern and methods.
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /service/contact-us
     * @param object $func    The handling function to be executed
     *
     * @return bool
     */
    public function match($methods, $pattern, $func)
    {
        $pattern = $this->setBaseRoute($pattern);

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = ['pattern' => $pattern, 'fn' => $func];
        }

        return $this;
    }

    /**
     * Shorthand for a route accessed using POST.
     *
     * @param string $pattern A route pattern such as /home/system
     * @param object $func    The handling function to be executed
     *
     * @return bool
     */
    public function post(string $pattern, $func)
    {
        $method = strtoupper(__FUNCTION__);
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }
        if (!$func instanceof \Closure) {
            return $this->override($pattern, $func, false, $method);
        }

        return $this->match($method, $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using DELETE.
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $func    The handling function to be executed
     *
     * @return bool
     */
    public function delete(string $pattern, $func)
    {
        $method = strtoupper(__FUNCTION__);
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        return $this->match($method, $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using PUT.
     *
     * @param string $pattern A route pattern such as /home/system
     * @param object $func    The handling function to be executed
     *
     * @return bool
     */
    public function put(string $pattern, $func)
    {
        $method = strtoupper(__FUNCTION__);
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        return $this->match($method, $pattern, $func);
    }

    /**
     * Shorthand for route accessed using patch.
     *
     * @param $pattern
     * @param $func
     *
     * @return bool
     */
    public function patch(string $pattern, $func)
    {
        $method = strtoupper(__FUNCTION__);

        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        return $this->match($method, $pattern, $func);
    }

    /**
     * Shorthand for a route accessed using OPTIONS.
     *
     * @param string $pattern A route pattern such as /home/system
     * @param object $func    The handling function to be executed
     *
     * @return bool
     */
    public function options(string $pattern, $func)
    {
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        return $this->match(strtoupper(__FUNCTION__), $pattern, $func);
    }

    /**
     * This method respond to any HTTP method.
     *
     * @param $pattern
     * @param $func
     *
     * @return bool
     */
    public function any(string $pattern, $func)
    {
        if (is_array($func)) {
            if ($this->isPatternMatches($pattern)) {
                $this->middleware = $func['middleware'];
            }
            $func = end($func);
        }

        return $this->match('GET|POST|PUT|PATCH|DELETE|OPTIONS', $pattern, $func);
    }

    /**
     * Customize the routing pattern using where.
     *
     * @param $key
     * @param $pattern
     *
     * @return $this
     */
    public function where($key, $pattern)
    {
        return $this->pattern($key, $pattern);
    }

    /**
     * Set custom route pattern for the routes.
     *
     * @param $key
     * @param $pattern
     *
     * @return $this
     */
    public function pattern($key, $pattern) : Router
    {
        $this->patterns[$key] = $pattern;

        return $this;
    }

    /**
     * Get defined routes patterns.
     *
     * @param $key
     * @return string
     */
    public function getPattern($key = null)
    {
        return isset($this->patterns[$key]) ? $this->patterns[$key] : $this->patterns;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Allow you to apply nested sub routing.
     *
     * @param          $groupRoute
     * @param callable $callback
     */
    public function group($groupRoute, \Closure $callback)
    {
        // Track current base path
        $curBaseRoute = $this->routeBasePath;
        // Build new route base path string
        $this->routeBasePath .= $groupRoute;

        // Call the Closure callback
        call_user_func(function () use ($callback) {
            return $callback($this);
        });

        // Restore original route base path
        $this->routeBasePath = $curBaseRoute;
    }

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
    public function resource(string $name, string $controller) : ResourceController
    {
        return $this->resourceController->resourceController($this, $name, $controller);
    }

    /**
     * Router to controller.
     *
     * @param $controller
     * @internal param $controller
     * @return mixed
     */
    public function routeController($controller)
    {
        return $this->getRouteController()
            ->setRouter($this)
            ->routeController($controller);
    }

    /**
     * @return Controller
     */
    public function getRouteController()
    {
        return new Controller();
    }

    public function getRouteResourceController()
    {
        return $this->resourceController;
    }

    /**
     * Route to controller implicitly based on HTTP verbs prefixed.
     *
     * @param $controller
     */
    public function controller($controller)
    {
        return $this->getRouteController()
            ->setRouter($this)
            ->implicitController($controller);
    }

    /**
     * @return unknown
     */
    public function urlRoutes()
    {
        return (isset($this->routes[$this->request->server->get('REQUEST_METHOD')])) ?
        $this->routes[$this->request->server->get('REQUEST_METHOD')] :
        null;
    }

    /**
     * Execute the router. Loop all defined routes,
     * and call function to handle request if matching pattern found.
     *
     * @param null $callback
     *
     * @return mixed
     */
    public function run($callback = null)
    {
        // Check before routing middle ware and trigger
        $this->beforeRoutingMiddleware();
        // Set after routing event
        $this->setAfterRoutingMiddleWare();
        // Handle all routes
        $handledRequest = 0;
        if (isset($this->routes[$this->request->server->get('REQUEST_METHOD')])) {
            $flag = (!is_null($this->afterRouter)) ? true : false;

            $handledRequest = $this->handle($this->routes[$this->request->server->get('REQUEST_METHOD')], $flag);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($handledRequest == 0) {
            if (!is_null($this->notFound) && is_callable($this->notFound)) {
                return call_user_func($this->notFound);
            }
        }
    }

    private function beforeRoutingMiddleWare()
    {
        // Handle all before middle wares
        if (isset($this->before[$this->request->server->get('REQUEST_METHOD')])) {
            $this->handle($this->before[$this->request->server->get('REQUEST_METHOD')]);
        }
    }

    /**
     * Handle a a set of routes. If a pattern match is found, execute the handling function.
     *
     * @param      $routes                   Collection of route patterns
     * @param bool $fireAfterRoutingCallback
     *
     * @return int The number of routes handled
     */
    private function handle($routes, $fireAfterRoutingCallback = false)
    {
        // Counter to keep track of the number of routes we've handled
        $handledRequest = 0;

        $i = 0;
        // Loop all routes
        foreach ($routes as $route) {
            if ($matches = $this->isPatternMatches($route['pattern'], true)) {
                if ($this->middleware) {
                    $this->handleMiddleware();
                }

                // Extract the matched URL (and only the parameters)
                $params = $this->extractParams($matches);
                array_unshift($params, $this);

                // call the handling function with the URL
                $this->handledRoute = call_user_func_array($route['fn'], $params);
                $this->container->set('response', $this->handledRoute);

                $handledRequest++;

                // If we need to quit, then quit
                if ($fireAfterRoutingCallback) {
                    // If a route was handled, perform the finish callback (if any)
                    $this->handle($this->afterRouter);
                }
            }
            $i++;
        }

        // Return the number of routes handled
        return $handledRequest;
    }

    /**
     * @param $uri
     *
     * @return mixed|string
     */
    public function removeIndexDotPhpAndTrillingSlash($uri)
    {
        return (strpos($uri, static::$indexPage) !== false) ?
        preg_replace(
            '/(\/+)/',
            '/',
            str_replace(static::$indexPage, '', rtrim($uri))
        ) :
        trim($uri);
    }

    /**
     * Define the current relative URI.
     *
     * @return string
     */
    public function getCurrentUri()
    {
        return $this->request->getCurrentUri();
    }

    /**
     * Get the base url.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->request->getBaseUrl();
    }

    /**
     * @param $pattern
     *
     * @return bool|mixed
     */
    public function hasNamedPattern($pattern)
    {
        return (string_has($pattern, '{:')) ? $this->replace($pattern) : false;
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    protected function replace($string)
    {
        foreach ($this->patterns as $key => $value) {
            $string = str_replace($key, $value, $string);
        }

        return $string;
    }

    /**
     * @param $matches
     *
     * @return array
     */
    private function extractParams($matches)
    {
        return array_map(
            function ($match) {
                $args = explode('/', trim($match, '/'));

                return isset($args[0]) ? $args[0] : null;
            },
            array_slice(
                $matches[0],
                1
            )
        );
    }

    private function setAfterRoutingMiddleWare()
    {
        if (isset($this->after[$this->request->server->get('REQUEST_METHOD')])) {
            $this->afterRouter = $this->after[$this->request->server->get('REQUEST_METHOD')];
        }
    }

    /**
     * Set the 404 handling function.
     *
     * @param object $func The function to be executed
     *
     * @return mixed|void
     */
    public function set404Page(callable $func)
    {
        $this->notFound = $func;

        return $this;
    }

    /**
     * Returns Request instance.
     *
     * @return object Request
     */
    public function request()
    {
        return $this->request;
    }

    private function getConfigParameter()
    {
        $data = [
            lcfirst(Config::get('global.config', 'default.controller')),
            lcfirst(Config::get('global.config', 'default.method')),
        ];

        return $data;
    }

    /**
     * Dispatch the request.
     *
     * @param $request
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function dispatch($request)
    {
        $this->request = $request;
        list($defaultController, $defaultAction) = $this->getConfigParameter();

        // If no argument passed or single slash call default controller
        if ($this->getCurrentUri() == '/' ||
            $this->getCurrentUri() == '/' . self::$indexPage
        ) {
            if ($defaultController != '') {

                $this->getRouteControllerInstance();

                list($controller, $action) = $this->getControllerAndAction(
                    $defaultController,
                    $defaultAction
                );

                return $this->handleControllerDependencies($controller, $action);
            }
        }

        try {
            $routeRequests = $this->getAppRoutes();

            return $this->getResponse();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set router instance into ResourceControllerTrait trait.
     *
     * @return $this
     */
    public function getRouteControllerInstance()
    {
        $this->resourceController->setRouter($this);

        return $this;
    }

    /**
     * @return callable
     */
    public function getAppRoutes()
    {
        $routes = function () {
            extract([
                'app' => $this->container->get('app'),
                'router' => $this,
            ]);

            require $this->container->get('routes.dir') . DS . 'Routes' . '.php';
        };

        return $routes();
    }

    /**
     * Set route Middleware.
     *
     * @param $middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Get middleware instance.
     *
     * @return mixed
     */
    public function getMiddlewares()
    {
        return $this->middleware;
    }

    /**
     * Returns the response stored in container.
     *
     * @return mixed
     */
    public function getResponse()
    {
        $container = $this->getContainer();

        return $container['response'];
    }

    /**
     * @internal param null $pipe
     * @internal param string $method
     *
     * @return null
     */
    public function handleMiddleware()
    {
        $this->runRouteWithinStack($this->request());
    }

    /* * @internal param $pipes
     * @internal param $method
     * @return mixed
     */
    public function runRouteWithinStack(Request $request)
    {
        return (new Pipeline($this->container))
            ->send($request)
            ->through([$this->middleware])
            ->run();
    }

    /**
     * Checks if pattern matches.
     *
     * @param $pattern
     * @param bool $routePattern
     * @return mixed
     */
    public function isPatternMatches($pattern, $routePattern = false)
    {
        $uri = $this->removeIndexDotPhpAndTrillingSlash($this->getCurrentUri());
        $hasPattern = $this->hasNamedPattern($pattern);
        $pattern = ($hasPattern == false) ? $pattern : $hasPattern;
        if (preg_match_all(
            '#^' . $pattern . '$#',
            $uri,
            $matches,
            PREG_SET_ORDER
        )) {
            return $matches;
        }
    }

    /**
     * Get url segments.
     *
     * @return array
     */
    public function getUrlSegments() : array
    {
        $newUrl = str_replace('/'.Router::$indexPage, '', rtrim($this->getCurrentUri()));

        return array_filter(explode('/', $newUrl));
    }
}
