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

use Cygnite\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

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
class Application extends SymfonyApplication implements ConsoleApplicationInterface
{
    protected $commandsStack = [
        'Cygnite\Console\Command\InitCommand',
        'Cygnite\Console\Command\GeneratorCommand',
        'Cygnite\Console\Command\MigrationCommand',
        'Cygnite\Console\Command\FormGeneratorCommand',
        'Cygnite\Console\Command\ModelGeneratorCommand',
        'Cygnite\Console\Command\ControllerGeneratorCommand',
    ];

    protected $stack = [];

    protected $cygnite;

    /**
     * Create Cyrus Console Application
     *
     * @param string $cygniteApplication
     * @param string $version
     */
    public function __construct($cygniteApplication, $version)
    {
        parent::__construct('Cygnite Framework: Console Application', $version);
        $this->cygnite = $cygniteApplication;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
    }

    /**
     * Add new command into stack
     *
     * @param $command
     * @return $this
     */
    public function setCommand($command)
    {
        $this->commandsStack = array_merge($this->commandsStack, $command);

        return $this;
    }


    /**
     * Get the Cygnite application instance.
     *
     * @return instance
     */
    public function getCygniteApplication()
    {
        return $this->cygnite;
    }


    /**
     * @param $command
     * @return \Symfony\Component\Console\Command\Command|void
     */
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command)
        {
            $command->setCygnite($this->cygnite);
        }

        parent::add($command);
    }

    /**
     * We will register all the cyrus console command into container
     *
     * @return void
     */
    public function registerCommands()
    {
        foreach ($this->commandsStack as $key => $command) {
            $this->resolveConsoleCommand('\\'.$command);
        }

        return $this;

        //var_dump($this->stack);
        //$this->addCommands($this->stack);
        //return true;
    }

    /**
     * Make command through the Cygnite application and
     * add command into the console.
     *
     * @param $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolveConsoleCommand($command)
    {
        //var_dump($this->cygnite->make($command));
        /*$command = $this->cygnite->make($command);
        $this->stack[] = $command;*/
        $this->add($this->cygnite->make($command));
    }

    /**
     * We will register all commands into Console
     *
     * @return true
     */
    /*public function execute()
    {
        $this->registerCommands();
        exit;
        $command = $this->cygnite->make($this->commandsStack[0]);
        //var_dump($command);
        $this->add($command);
        //parent::setDefaultCommand($command->getName());
        parent::run();
    }*/

    /*protected function execute()
    {
        $input = new Symfony\Component\Console\Input\InputInterface();
        $output = new Symfony\Component\Console\Output\OutputInterface();

        $this->input = $input;

        $this->output = $output;

        return parent::run($input, $output);

    }*/
}
