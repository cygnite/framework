<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Console;

use Cygnite\Console\Foundation\Application as ConsoleApplication;
use Cygnite\Foundation\Application;

/**
 * Class CraftApplication.
 */
class CraftApplication
{
    private $version;

    protected $commands = [];

    /**
     * @param $version
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * Register your console commands here.
     *
     * @param $commands
     *
     * @return $this
     */
    public function register($commands)
    {
        $this->commands = $commands;

        return $this;
    }

    /**
     * We will run Cygnite Console Application.
     */
    public function run()
    {
        $console = new ConsoleApplication(new Application(), $this->version);

        /*
         | We will also register Application Console commands
         | User can register multiple commands apart from core
         | commands and run on the fly
         */
        $console->setCommand($this->commands)
            ->registerCommands()
            ->run();
    }
}
