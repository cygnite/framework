<?php
namespace Cygnite\Bootstrappers;

interface BootstrapperDispatcherInterface
{
    public function execute();

    public function create(string $class);
}