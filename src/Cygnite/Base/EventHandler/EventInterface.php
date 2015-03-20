<?php
namespace Cygnite\Base\EventHandler;

interface EventInterface
{
    /**
     * @param $eventName
     * @param $callback
     * @return mixed
     */
    public function attach($eventName, $callback);

    /**
     * @param       $eventName
     * @param array $data
     * @return mixed
     */
    public function trigger($eventName, $data = array());

    /**
     * @param null $event
     * @return mixed
     */
    public function flush($event = null);
}