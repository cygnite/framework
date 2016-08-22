<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Pipeline;

use Closure;
use Cygnite\Container\ContainerAwareInterface;

/**
 * Class Pipeline.
 */
class Pipeline implements PipelineInterface
{
    private $request;

    private $pipes = [];

    public $defaultMethod = 'handle';

    private $method;

    private $callback;

    private $container;

    /**
     * Constructor.
     *
     * @param null $container
     */
    public function __construct($container = null)
    {
        if (!is_null($container)) {
            $this->container = $container;
        }
    }

    /**
     * Set container into pipeline for resolving objects.
     *
     * @param ContainerAwareInterface $container
     *
     * @return $this
     */
    public function setContainer(ContainerAwareInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Send request through pipeline.
     *
     * @param $request
     *
     * @return $this
     */
    public function send($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Apply filters over pipes before executing.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function then(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Pass request through the pipeline.
     *
     * @param array $pipes
     * @param null  $method
     *
     * @return $this
     */
    public function through(array $pipes, $method = null)
    {
        $this->pipes = $pipes;
        $this->method = !is_null($method) ? $method : $this->defaultMethod;

        return $this;
    }

    /**
     * Return method name.
     *
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Run all pipeline requests.
     *
     * @return mixed
     */
    public function run()
    {
        return call_user_func(
            array_reduce(
                array_reverse($this->pipes),
                $this->createPipelineCallback(),
                function ($request) {
                    return ($this->callback === null) ? $request : call_user_func($this->callback, $request);
                }
            ),
            $this->request
        );
    }

    /**
     * Create pipeline callback.
     *
     * @throws PipelineException thrown if method doesn't exists
     *
     * @return callable
     */
    private function createPipelineCallback()
    {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return call_user_func($pipe, $request, $stack);
                } elseif (!is_object($pipe)) {
                    list($callback, $parameters) = $this->createParameters($pipe, $request, $stack, true);
                } else {
                    if (!method_exists($pipe, $this->method)) {
                        throw new PipelineException(sprintf("%s::%s doesn't exist", get_class($pipe), $this->method));
                    }

                    list($callback, $parameters) = $this->createParameters($pipe, $request, $stack);
                }

                return $this->call($callback, $parameters);
            };
        };
    }

    /**
     * Form a array of callback and parameters.
     *
     * @param $pipe
     * @param $request
     * @param $stack
     * @param bool $isString
     *
     * @throws PipelineException
     *
     * @return array
     */
    private function createParameters($pipe, $request, $stack, $isString = false)
    {
        if ($isString) {
            list($name, $parameters) = $this->parsePipeString($pipe);

            if (!is_object($this->container)) {
                throw new PipelineException(sprintf('%s expects container instance', get_class($this)));
            }

            $pipe = ($this->container->has($name)) ? $this->container->get($name) : $this->container->make($name);
            $parameters = array_merge([$request, $stack], $parameters);
        } else {
            $parameters = [$request, $stack];
        }

        return [[$pipe, $this->method], $parameters];
    }

    /**
     * @param $callback
     * @param array $parameters
     *
     * @return mixed
     */
    private function call($callback, $parameters = [])
    {
        return call_user_func_array($callback, $parameters);
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param string $pipe
     *
     * @return array
     *
     * @link https://github.com/laravel/framework/blob/5.2/src/Illuminate/Pipeline/Pipeline.php#L160
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }
}
