<?php
namespace Cygnite\Mvc\Controller;

interface ServiceControllerInterface
{
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function __set($key, $value);

    /**
     * @param $key
     * @return mixed
     */
    public function &__get($key);
}
