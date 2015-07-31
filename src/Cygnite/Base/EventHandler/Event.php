<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Base\EventHandler;

use Closure;
use Cygnite\Base\EventHandler\EventRegistrarTrait;

/**
 * Class Event
 *
 * @package Cygnite\Base\EventHandler
 * @author  Sanjoy Dey
 */

class Event implements EventInterface
{
    use EventRegistrarTrait;

    protected $events = [];

    protected $listen = [];

    /**
     * @param Closure $callback
     * @return static
     */
    public static function create(Closure $callback = null)
    {
        // Check if $callback is instance of Closure we return callback
        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback(new static);
        }

        // Return instance of the Event Handler
        return new static;
    }

    /**
     * Attach the new event to event stack
     *
     * @param $name
     * @param $callback
     * @return mixed|void
     */
    public function attach($name, $callback)
    {
        if (is_null($name)) {
            throw new \RuntimeException(sprintf("Event name cannot be empty in %s", __FUNCTION__));
        }

        if (is_null($callback)) {
            throw new \RuntimeException(sprintf("Empty parameter passed as callback in %s function", __FUNCTION__));
        }


        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }

        $this->events[$name][] = $callback;

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
    public function trigger($name, $data = [])
    {
        if (!isset($this->events[$name])) {
            throw new \Exception("Event '$name' not found in ".__CLASS__." in stack!");
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

    private function callFunction($callback, $data)
    {
        $exp = explode('@', $callback);

        if (method_exists($instance = new $exp[0], $exp[1])) {
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
        $class = '\\' . str_replace('_', '\\', $expression[0]);
        call_user_func([new $class, $expression[1]]);
    }

    /**
     * Flush the event
     *
     * @param string $event
     * @return mixed|void
     */
    public function flush($event = null)
    {
        if (!is_null($event)) {
            unset($this->events[$event]);
        } else {
            unset($this->events);
        }
    }
}
