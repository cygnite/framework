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

/**
 * Class Event
 *
 * @package Cygnite\Base\EventHandler
 * @author  Sanjoy Dey
 */

class Event implements EventInterface
{
    protected $events = array();

    /**
     * @param Closure $callback
     * @return static
     */
    public static function create(Closure $callback = null)
    {
        // Check if $callback is instance of Closure we return callback
        if (!is_null($callback) && $callback instanceof Closure) {
            return $callback(new Static);
        }

        // Return instance of the Event Handler
        return new Static;
    }

    /**
     * Attach the new event to event stack
     *
     * @param $name
     * @param $callback
     */
    public function attach($name, $callback)
    {

        if (!isset($this->events[$name])) {
            $this->events[$name] = array();
        }

        $this->events[$name][] = $callback;
    }

    /**
     * We will check whether event is registered,
     * if so we will trigger the event.
     *
     * @param       $name
     * @param array $data
     * @return mixed
     */
    public function trigger($name, $data = array())
    {
        foreach ($this->events[$name] as $callback) {

            if (is_object($callback) && ($callback instanceof Closure)) {
                $callback($name, $data);
            }

            if (strpos($callback, '@')) {
                return $this->callFunction($callback, $data);
            }

            if (strpos($callback, '::')) {
                return $this->callUserFunctionEvent($callback);
            }

            if (is_string($callback) && strpos($callback, '@') == false) {
                call_user_func($callback, $data);
            }
        }
    }

    private function callFunction($callback, $data)
    {
        $exp = explode('@', $callback);

        if (method_exists($instance = new $exp[0], $exp[1])) {
            return call_user_func_array(array($instance, $exp[1]), array($data));
        }
    }

    /**
     * @param $callback
     */
    private function callUserFunctionEvent($callback)
    {
        $class = null;
        $expression = array();
        $expression = explode('::', $callback);
        $class = '\\' . str_replace('_', '\\', $expression[0]);
        call_user_func(array(new $class, $expression[1]));
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