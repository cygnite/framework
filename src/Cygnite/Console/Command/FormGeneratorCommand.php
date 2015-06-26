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

/*
 * Form Generator Command
 *
 * Form generator command class used to generate form using Cygnite CLI.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
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

    public static function __callStatic($method, $arguments = [])
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
            Inflector::tabilize($this->table)
        )->getColumns();
    }

    /**
     * We will execute the crud command and generate files
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        $this->applicationDir = CYGNITE_BASE.DS.APPPATH;
        $this->generateForm();

        $output->writeln("<info>Form ".APPPATH."/components/form/".Inflector::classify($this->table)."Form".EXT." Generated Successfully By Cygnite Cli.</info>");
    }


    /**
     * We will generate Form
     */
    private function generateForm()
    {
        // Generate Form Component class
        $controllerInstance = Controller::instance($this->columns, $this->viewType, $this);

        $formTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('components').DS.'Form'.DS;

        $form = new Form($controllerInstance, $this, $this->inflect);
        $form->setFormTemplatePath($formTemplateDir);

        $form->generate();
    }
}
