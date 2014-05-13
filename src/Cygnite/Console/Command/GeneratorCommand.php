<?php
namespace Cygnite\Console\Command;

use Cygnite\Application;
use Cygnite\Inflector;
use Cygnite\Database;
use Cygnite\Database\Schema;
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
 * @Filename           :  GeneratorCommand.php
 * @Description        :  Generator Command class used to generate crud application using Cygnite CLI. 
 *                        Cygnite Cli driven by Symfony2 Console Component.
 * @Author             :  Sanjoy Dey
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0.6
 * @File Source
 *
 */

class GeneratorCommand extends Command
{

	private $tableSchema;

    public $applicationDir;

    public $controller;

    public $model;

    public $database;

    private $inflect;

    private $columns;

    private $output;
	
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
	
    protected function configure()
    {
        $this->setName('generate:crud')
             ->setDescription('Generate Crud By Cygnite CLI')
             ->addArgument('name', InputArgument::OPTIONAL, 'Your Controller Name ?')
             ->addArgument('model', InputArgument::OPTIONAL, 'Your Model Name ?')
             ->addArgument('database', InputArgument::OPTIONAL, '')
            //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    private function getColumns()
    {
        return $this->tableSchema->connect(
            $this->database,
            $this->inflect->fromCamelCase($this->model)
        )->getColumns();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inflect = new Inflector;

        $this->controller = $this->inflect->covertAsClassName($input->getArgument('name')).'Controller';
        $this->model = $this->inflect->covertAsClassName($input->getArgument('model'));
        $this->database = (!is_null($input->getArgument('database'))) ?
                           $input->getArgument('database') :
                           $this->tableSchema->getDefaultDatabaseConnection();

        $this->columns = $this->getColumns();

        $this->applicationDir = BASE_PATH.DS.APP_PATH;
        $this->output = $output;

        $this->generateController();
        $this->generateModel();
        $this->generateViews();

        $output->writeln("<info>Crud process ended successfully by Cygnite CLI </info>");
    }

    private function generateController()
    {
        // Generate Controller class
        $controllerInstance = Controller::instance($this->inflect, $this->columns);

        $controllerTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('controllers').DS;

        $controllerInstance->setControllerTemplatePath($controllerTemplateDir);
        $controllerInstance->setApplicationDirectory($this->applicationDir);

        $controllerInstance->setControllerName($this->controller);
        $controllerInstance->setModelName($this->model);
        $controllerInstance->updateTemplate();
        $controllerInstance->generateControllerTemplate();
        $controllerInstance->generate();

        $this->output->writeln("Controller $this->controller generated successfully");
    }

    private function generateModel()
    {
        $modelInstance = Model::instance($this->inflect, $this);
        $modelTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('models').DS;

        $modelInstance->setModelTemplatePath($modelTemplateDir);
        $modelInstance->updateTemplate();
        $modelInstance->generate();
        $this->output->writeln("Model $this->model generated successfully");
    }

    private function generateViews()
    {
        $viewInstance = View::instance($this->inflect, $this);
        $viewTemplateDir = dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('views').DS;
        $viewInstance->setTableColumns($this->columns);
        $viewInstance->setViewTemplatePath($viewTemplateDir);
        $viewInstance->generateLayout('layout.main');
        $viewInstance->generateViews();

        $this->output->writeln("Views generated in ".str_replace("Controller", "", $this->controller)." directory");
    }


}
