<?php
namespace Cygnite\Base;

use Closure;
use Cygnite\Reflection;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package            :  Packages
 * @Sub Packages       :  Base
 * @Filename           :  Event
 * @Description        :  Create event and trigger it dynamically. Allow you
 *                        event driven programming.
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0
 *
 *
 */

class Event
{
    protected $events = array();

    /**
     * @param       $method
     * @param array $arguments
     * @return $this
     */
    public function __call($method, $arguments = array())
	{
		if ($method == 'instance') {
			return $this;
		}
	}

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments = array())
    {
		if ($method == 'instance') {
			return call_user_func_array(array(new self, $method), array($arguments));
		}
    }

    /**
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
                $exp = explode('@', $callback);

                if (method_exists($obj = new $exp[0], $exp[1])) {
                    return call_user_func_array(array(new $exp[0], $exp[1]), array($data));
                }

            }

            if (strpos($callback, '::')) {
                $class = null;
                $expression = array();
                $expression = explode('::', $callback);
                //show($expression);
                $class = '\\'.str_replace('_', '\\', $expression[0]);
                call_user_func(array(new $class, $expression[1]));
            }

            if (is_string($callback) && strpos($callback, '@') == false) {

                call_user_func($callback, $data);
            }
        }
    }

    /**
     * @param string $event
     */
    public function flush($event = "")
    {
        if ($event !== "") {
            unset($this->events[$event]);
        } else {
            unset($this->events);
        }
    }
}