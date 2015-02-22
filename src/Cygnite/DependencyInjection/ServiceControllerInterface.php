<?php
namespace Cygnite\DependencyInjection;


interface ServiceControllerInterface
{
    public function __set($key,$value);

    public function &__get($key);
}