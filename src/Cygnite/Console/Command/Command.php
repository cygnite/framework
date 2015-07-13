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

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
    protected $name;

    protected $description;

    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    public function __construct()
    {
        /*
         | We will set command name and descriptions here
         */
        parent::__construct($this->name);

        $this->setDescription($this->description);
    }

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @param $input
     * @return $this
     */
    protected function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param $output
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
     * We will get the console helper instance
     *
     * @param $name
     * @return mixed
     */
    public function helper($name)
    {
        return $this->getHelperSet()->get($name);
    }

    /**
     * Get Process helper
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
     * @param  string  $question
     * @param  bool    $default
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
     * @param  string  $question
     * @param  string  $default
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
     * @param  string  $string
     * @return void
     */
    public function info($string)
    {
        $this->getOutput()->writeln("<info>$string</info>");
    }

    /**
     * Write as standard string output
     *
     * @param  string  $string
     * @return void
     */
    public function write($string)
    {
        $this->getOutput()->writeln($string);
    }

    /**
     * Write as comment string.
     *
     * @param  string  $string
     * @return void
     */
    public function comment($string)
    {
        $this->getOutput()->writeln("<comment>$string</comment>");
    }

    /**
     * Write string as question.
     *
     * @param  string  $string
     * @return void
     */
    public function question($string)
    {
        $this->getOutput()->writeln("<question>$string</question>");
    }

    /**
     * Write string as error.
     *
     * @param  string  $string
     * @return void
     */
    public function error($string)
    {
        $this->getOutput()->writeln("<error>$string</error>");
    }

    public function setCygnite($cygnite)
    {
        $this->cygnite = $cygnite;
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);

        return parent::run($input, $output);
    }
}
