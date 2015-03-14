<?php
namespace Cygnite\Base;

interface EventInterface
{
    public function attach($eventName, $callback);

    public function trigger($eventName, $data = array());

    public function flush($event = null);
}