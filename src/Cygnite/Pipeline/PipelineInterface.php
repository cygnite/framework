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

use Cygnite\Container\ContainerAwareInterface;

interface PipelineInterface
{
    /**
     * Set container into pipeline for resolving objects.
     *
     * @param ContainerAwareInterface $container
     *
     * @return $this
     */
    public function setContainer(ContainerAwareInterface $container);

    /**
     * Send request through pipeline.
     *
     * @param $request
     *
     * @return $this
     */
    public function send($request);

    /**
     * Apply filters over pipes before executing.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function then(callable $callback);

    /**
     * Pass request through the pipeline.
     *
     * @param array $pipes
     * @param string|null  $method
     *
     * @return $this
     */
    public function through(array $pipes, string $method = null);

    /**
     * Run all pipeline requests.
     *
     * @return mixed
     */
    public function run();
}
