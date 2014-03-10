<?php
namespace Cygnite\Base;

use Closure;
use Cygnite\Reflection;

class Event
{
    protected $events = array();

	/*
    public static function __callStatic($method, $arguments)
    {
        var_dump($method);

        if ($method == 'attach') {
            Reflection::getInstance();
            //return call_user_func_array(array(, substr($method, 0, 6)), $params);
        } else if ($method == 'trigger') {

        }
    }*/

    public function __call($method, $arguments = array())
	{
		if ($method == 'instance') {
			return $this;
		}
	}
	
    public static function __callStatic($method, $arguments = array())
    {
		if ($method == 'instance') {
			return call_user_func_array(array(new self, $method), array($arguments));
		}
    }

    public function attach($eventName, $callback)
    {
        /*if (is_array($eventName)) {

        }*/

        if (!isset($this->events[$eventName])) {
            $this->events[$eventName] = array();
        }

        $this->events[$eventName][] = $callback;
    }


    public function trigger($eventName, $data = array())
    {
        foreach ($this->events[$eventName] as $callback) {
             // echo $eventName."<br>";

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
                $class = '';
                //show($callback);
                $expression = "";
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

    public function flush($event = "")
    {
        if ($event !== "") {
            unset($this->events[$event]);
        } else {
            unset($this->events);
        }
    }
}