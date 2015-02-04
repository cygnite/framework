<?php
namespace Cygnite\Console\Command;

use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Database;
use Cygnite\Database\Schema;
use Cygnite\Console\Generator\Form;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Generator\View;
use Cygnite\Console\Generator\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so that I can send you a copy immediately.
 *
 * @Package            :  Console
 * @Filename           :  FormGeneratorCommand.php
 * @Description        :  Generator Command class used to generate crud application using Cygnite CLI.
 *                        Cygnite Cli driven by Symfony2 Console Component.
 * @Author             :  Sanjoy Dey
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0.6
 * @File Source
 *
 */

class FormGeneratorCommand extends Command
{

    private $tableSchema;

    public $applicationDir;

    public $controller;

    public $table;

    public $database;

    private $inflect;

    public $columns;

    private $output;

    private $viewType;

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self();
        }
    }

    public function setSchema($table)
    {
        $this->tableSchema = $table;
    }

    /**
     * Configure the form generate command
     */
    protected function configure()
    {
        $this->setName('generate:form')
             ->setDescription('Generate Form using Cygnite CLI')
             ->addArgument('name', InputArgument::OPTIONAL, '')
             ->addArgument('database', InputArgument::OPTIONAL, '');
    }

    /**
     * We will get all column schema from database
     * @return mixed
     */
    private function getColumns()
    {
        return $this->tableSchema->connect(
            $this->database,
            $this->inflect->tabilize($this->table)
        )->getColumns();
    }

    /**
     * We will execute the crud command and generate files
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inflect = new Inflector;
        /** Check for argument database name if not given we will use default
         *  database connection
         */
        $this->database = (!is_null($input->getArgument('database'))) ?
            $input->getArgument('database') :
            $this->tableSchema->getDefaultDatabaseConnection();

        $this->table = (!is_null($input->getArgument('name'))) ?
            $input->getArgument('name') :
            'Form';

        $this->columns = $this->getColumns();
        $this->applicationDir = BASE_PATH.DS.APP_PATH;
        $this->generateForm();

        $output->writeln("<info>Form apps/components/form/".$this->inflect->classify($this->table)."Form Generated Successfully By Cygnite Cli.</info>");
    }


    /**
     * We will generate Form
     */
    private function generateForm()
    {
        // Generate Form Component class
        $controllerInstance = Controller::instance($this->inflect, $this->columns, $this->viewType, $this);

        $formTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('components').DS.'Form'.DS;

        $form = new Form($controllerInstance, $this, $this->inflect);
        $form->setFormTemplatePath($formTemplateDir);

        $form->generate();
    }
}
