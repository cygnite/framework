<?php
namespace Cygnite\Bootstrappers;

interface BootstrapperInterface
{
    /**
     * Registers anything in IoC container or do some configuration
     * before running application
     *
     */
    public function run();
}