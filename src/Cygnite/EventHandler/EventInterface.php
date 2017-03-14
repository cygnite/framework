<?php

namespace Cygnite\EventHandler;

interface EventInterface
{
    /**
     * @param $eventName
     * @param $callback
     *
     * @return mixed
     */
    public function register(string $eventName, $callback);

    /**
     * @param       $eventName
     * @param array $data
     *
     * @return mixed
     */
    public function dispatch(string $eventName, $data = []);

    /**
     * @param null $event
     *
     * @return mixed
     */
    public function remove($event = null);
}
