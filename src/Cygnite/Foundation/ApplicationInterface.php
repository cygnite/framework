<?php

namespace Cygnite\Foundation;

use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Http\Requests\Request;

/**
 * interface ApplicationInterface.
 */
interface ApplicationInterface
{
    /**
     * Returns a Instance of Application either as Closure
     * or static instance.
     *
     * @param \Closure $callback
     * @param array $argument
     *
     * @return Application
     */
    public static function instance(\Closure $callback = null, $argument = []);

    /**
     * Create an instance of the class and return it.
     *
     * @param $class
     * @param array $arguments
     * @return mixed
     */
    public function compose(string $class, array $arguments = []);

    /**
     * Resolve namespace via container.
     *
     * return @object
     */
    public function resolve(string $class, array $arguments = []);

    /**
     * Set language to the translator.
     *
     * @param null $localization
     *
     * @return locale
     */
    public function setLocale($localization = null);

    /**
     * Set all configurations and boot application.
     *
     * @param \Cygnite\Http\Requests\Request $request
     * @return $this
     */
    public function bootApplication(Request $request);

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware.
     *
     * @return bool
     */
    public function beforeBootingApplication();

    /**
     * We will trigger after booting application event if it is
     * activated in Event Middleware.
     *
     * @return bool
     */
    public function afterBootingApplication();

    /**
     * Indicate if Application booted or not.
     *
     * @return bool
     */
    public function isBooted();

    /**
     * Throw an HttpException with the given message.
     *
     * @param int    $code
     * @param string $message
     * @param array  $headers
     *
     * @return void
     */
    public function abort(int $code, string $message = '', array $headers = []);
}
