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

    protected $app;

    /**
     * @param $version
     */
    public function __construct($app, $version)
    {
        $this->app = $app;
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
    public function execute()
    {
        //$console = new ConsoleApplication($this->app, $this->version);

        /*
         | We will also register Application Console commands
         | User can register multiple commands apart from core
         | commands and run on the fly
         */
        $this->app->setCommand($this->commands)
            ->registerCommands()
            ->run();
    }
}
