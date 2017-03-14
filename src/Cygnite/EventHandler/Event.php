<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\EventHandler;

use Closure;

/**
 * Class Event.
 *
 * @author  Sanjoy Dey
 */
class Event implements EventInterface
{
    use EventRegistrarTrait;

    /** @var array */
    protected $events = [];

    /** @var array */
    protected $listen = [];

    /**
     * Returns a Event Handle instance.
     *
     * @param Closure $callback
     * @return static
     */
    public static function create(Closure $callback = null)
    {
        // Check if $callback is instance of Closure we return callback
        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback(new static());
        }

        // Return instance of the Event Handler
        return new static();
    }

    /**
     * Get registered event listeners.
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners(string $eventName) : array
    {
        if (!isset($this->events[$eventName])) {
            return [];
        }

        return $this->events[$eventName];
    }

    /**
     * Check Is Event Registered.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName) : bool
    {
        return isset($this->events[$eventName]) && count($this->events[$eventName]) > 0;
    }

    /**
     * Register a new event to event stack.
     *
     * @param $name
     * @param $callback
     * @return mixed|void
     */
    public function register(string $name, $callback) : Event
    {
        if (is_null($name)) {
            throw new \RuntimeException(sprintf('Event name cannot be empty in %s', __FUNCTION__));
        }

        if (is_null($callback)) {
            throw new \RuntimeException(sprintf('Empty parameter passed as callback in %s function', __FUNCTION__));
        }

        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }

        if (!in_array($callback, $this->events[$name])) {
            $this->events[$name][] = $callback;
        }

        return $this;
    }

    /**
     * We will check whether event is registered,
     * if so we will trigger the event.
     *
     * @param       $name
     * @param array $data
     * @throws \Exception
     * @return mixed
     */
    public function dispatch(string $name, $data = [])
    {
        if (!isset($this->events[$name])) {
            throw new \Exception("Event '$name' not found in ".__CLASS__.' in stack!');
        }

        foreach ($this->events[$name] as $callback) {
            switch ($callback) {
                case is_object($callback) && ($callback instanceof Closure):
                    return $callback($name, $data);
                    break;
                case string_has($callback, '@'):
                    return $this->callFunction($callback, $data);
                    break;
                case string_has($callback, '::'):
                    return $this->callUserFunctionEvent($callback);
                    break;
                case is_string($callback) && !string_has($callback, '@'):
                    call_user_func($callback, $data);
                    break;
            }
        }
    }

    /**
     * Call event listeners.
     *
     * @param $callback
     * @param $data
     * @return mixed
     */
    private function callFunction($callback, $data)
    {
        $exp = explode('@', $callback);

        if (method_exists($instance = new $exp[0](), $exp[1])) {
            return call_user_func_array([$instance, $exp[1]], [$data]);
        }
    }

    /**
     * @param $callback
     */
    private function callUserFunctionEvent($callback)
    {
        $class = null;
        $expression = [];
        $expression = explode('::', $callback);
        $class = '\\'.str_replace('_', '\\', $expression[0]);
        call_user_func([new $class(), $expression[1]]);
    }

    /**
     * Remove registered event.
     *
     * @param string $event
     * @return mixed|void
     */
    public function remove($event = null)
    {
        if (!is_null($event)) {
            unset($this->events[$event]);
        }

        $this->events = [];
    }
}
