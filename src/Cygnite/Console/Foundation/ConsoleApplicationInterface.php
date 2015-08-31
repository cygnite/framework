<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Console\Foundation;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Cygnite Console Application
 *
 * Class Application
 *
 * This class is the entry point of Console component. It is the
 * a middle ware of all your console commands.
 * Cygnite CLI powered by Symfony2 Console Component.
 *
 * @package Cygnite\Console\Foundation
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
interface ConsoleApplicationInterface
{
    /**
     * Add new command into stack
     *
     * @param $command
     * @return $this
     */
    public function setCommand($command);

    /**
     * @param $command
     * @return \Symfony\Component\Console\Command\Command|void
     */
    public function add(SymfonyCommand $command);

    /**
     * We will register all commands into Console
     *
     * @return true
     */
    //public function execute($input, $output);
}
