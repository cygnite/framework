<?php

namespace Cygnite\Foundation;

use Cygnite\Container\ContainerAwareInterface;

/**
 * interface ApplicationInterface.
 */
interface ApplicationInterface extends ContainerAwareInterface
{
    /**
     * Returns a Instance of Application either as Closure
     * or static instance.
     *
     * @param Closure $callback
     * @param array   $argument
     *
     * @return Application
     */
    public static function instance(\Closure $callback = null, $argument = []);

    /**
     * Create an instance of the class and return it.
     *
     * @param $class
     *
     * @return mixed
     */
    public function compose($class, $arguments = []);

    /**
     * Service Closure callback.
     *
     * @param callable $callback
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function service(\Closure $callback);

    /**
     * Override parent method.
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function set($key, $value);

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
     * @return $this
     */
    public function bootApplication();

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
    public function abort($code, $message = '', array $headers = []);
}
