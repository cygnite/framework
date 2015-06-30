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

use Cygnite\Foundation\Application as CygniteApplication;
use Cygnite\Console\Foundation\Application as ConsoleApplication;

/**
 * Class CygniteConsoleApplication
 *
 * @package Cygnite\Console
 */
class CygniteConsoleApplication
{
    private $version;

    /**
     * @param $version
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * We will run Cygnite Console Application
     */
    public function run()
    {
        $app = CygniteApplication::instance();

        $console = new ConsoleApplication($app, $this->version);

        $console->registerCommands()->run();
    }
}