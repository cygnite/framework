<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Base;

use Closure;
use Cygnite\Reflection;

/**
 * Class Event
 *
 * @package Cygnite\Base
 * @author  Sanjoy Dey
 */

class Event implements EventInterface
{
    protected $events = array();

    /**
     * Attach the new event to event stack
     *
     * @param $eventName
     * @param $callback
     */
    public function attach($eventName, $callback)
    {

        if (!isset($this->events[$eventName])) {
            $this->events[$eventName] = array();
        }

        $this->events[$eventName][] = $callback;
    }

    /**
     * We will check whether event is registered,
     * if so we will trigger the event.
     *
     * @param       $eventName
     * @param array $data
     * @return mixed
     */
    public function trigger($eventName, $data = array())
    {
        foreach ($this->events[$eventName] as $callback) {

            if (is_object($callback) && ($callback instanceof Closure)) {
                $callback($eventName, $data);
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
        $class = null; $expression = array();
        $expression = explode('::', $callback);
        $class = '\\'.str_replace('_', '\\', $expression[0]);
        call_user_func(array(new $class, $expression[1]));
    }
    /**
     * Flush the event
     *
     * @param string $event
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