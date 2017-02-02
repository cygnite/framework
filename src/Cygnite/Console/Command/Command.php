<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Console\Command;

use Cygnite\Console\Foundation\Application;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
    /**
     * @var name of the command
     */
    protected $name;

    /**
     * @var description of command
     */
    protected $description;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    protected $cygnite;

    public function __construct()
    {
        /*
         | We will set command name and descriptions here
         */
        parent::__construct($this->name);
        $this->setConfiguration();
    }

    /**
     * Set configuration for console command
     * We will set command description, arguments and optional
     * parameters into console command.
     *
     * @return void
     */
    protected function setConfiguration()
    {
        $this->setDescription($this->description);
        $this->configureArguments($this->arguments, 'addArgument');
        $this->configureArguments($this->options);
    }

    /**
     * We will add arguments and optional parameters into console
     * command.
     *
     * @param        $consoleInputs
     * @param string $method
     */
    protected function configureArguments($consoleInputs, $method = 'addOption')
    {
        /*
         | We will loop through all the arguments passed from console
         | and set all required and optional parameters
         | into console command
         |
         */
        foreach ($consoleInputs as $arguments) {
            call_user_func_array([$this, $method], $arguments);
        }
    }

    /**
     * @param $input
     *
     * @return $this
     */
    protected function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param $output
     *
     * @return $this
     */
    protected function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * We will get the console helper instance.
     *
     * @param $name
     *
     * @return mixed
     */
    public function helper($name)
    {
        return $this->getHelperSet()->get($name);
    }

    /**
     * Get Process helper.
     *
     * @return mixed
     */
    public function process()
    {
        return $this->helper('process');
    }

    /**
     * We will ask user to confirm a question.
     *
     * @param string $question
     * @param bool   $default
     *
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        $question = new ConfirmationQuestion("<question>{$question}</question> ", $default);

        return $this->helper('question')->ask($this->input, $this->output, $question);
    }

    /**
     * We will ask user for input.
     *
     * @param string $question
     * @param string $default
     *
     * @return string
     */
    public function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->helper('question')->ask($this->input, $this->output, $question);
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     *
     * @return void
     */
    public function info($string)
    {
        $this->getOutput()->writeln("<info>$string</info>");
    }

    /**
     * Write as standard string output.
     *
     * @param string $string
     *
     * @return void
     */
    public function write($string)
    {
        $this->getOutput()->writeln($string);
    }

    /**
     * Write as comment string.
     *
     * @param string $string
     *
     * @return void
     */
    public function comment($string)
    {
        $this->getOutput()->writeln("<comment>$string</comment>");
    }

    /**
     * Write string as question.
     *
     * @param string $string
     *
     * @return void
     */
    public function question($string)
    {
        $this->getOutput()->writeln("<question>$string</question>");
    }

    /**
     * Write string as error.
     *
     * @param string $string
     *
     * @return void
     */
    public function error($string)
    {
        $this->getOutput()->writeln("<error>$string</error>");
    }

    /**
     * Set Cygnite Application.
     *
     * @param $cygnite
     */
    public function setCygnite($cygnite)
    {
        $this->cygnite = $cygnite;
    }

    /**
     * Returns Cygnit Console Application Object.
     *
     * @return mixed
     */
    public function getConsoleApplication()
    {
        return $this->cygnite;
    }

    /**
     * Run the console command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);

        return parent::run($input, $output);
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'process') ? 'process' : 'handle';

        return $this->{$method}();
    }

    /**
     * Call any console command.
     *
     * @param string $command
     * @param array $arguments
     * @return int
     */
    public function callCommand(string $command, array $arguments = [])
    {
        $newCommand = $this->getApplication()->find($command);

        $arguments['command'] = $command;

        return $newCommand->run(new ArrayInput($arguments), $this->getOutput());
    }

    /**
     * Get the value from command argument.
     *
     * @param string $key
     *
     * @return string|array
     */
    public function argument($name)
    {
        $input = $this->getInput();

        return (is_null($name)) ? $input->getArguments() : $input->getArgument($name);
    }

    /**
     * Get optional parameter from command argument.
     *
     * @param string $key
     *
     * @return string|array
     */
    public function option($name)
    {
        $input = $this->getInput();

        return (is_null($name)) ? $input->getOptions() : $input->getOption($name);
    }
}
