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
use Cygnite\Database\Table;
use Cygnite\Database\Schema;
use Cygnite\Console\Generator\Form;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Generator\View;
use Cygnite\Console\Generator\Controller;
use Cygnite\Console\Command\Command;
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
    public $applicationDir;
    public $controller;
    public $table;
    public $tableName;
    public $database;
    public $columns;
    private $viewType;

    protected $name = 'generate:form';

    protected $description = 'Generate Form using Cygnite CLI';

    /**
     * @param Table $table
     * @throws \InvalidArgumentException
     */
    public function __construct(Table $table)
    {
        parent::__construct();

        if (!$table instanceof Table) {
            throw new \InvalidArgumentException(sprintf('Constructor parameter should be instance of %s.', $table));
        }

        $this->table = $table;
    }

    public function table()
    {
        return $this->table;
    }

    /**
     * Configure the form generate command
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '')
             ->addArgument('database', InputArgument::OPTIONAL, '');
    }

    /**
     * We will execute the crud command and generate files
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input)->setOutput($output);

        /**
         | Check for argument database name if not given we will use default
         |  database connection
         */
        $this->database = (!is_null($input->getArgument('database'))) ?
            $input->getArgument('database') :
            $this->table()->getDefaultDatabaseConnection();

        $this->tableName = (!is_null($input->getArgument('name'))) ?
            $input->getArgument('name') :
            'Form';

        $this->columns = $this->getColumns();
        $this->applicationDir = realpath(CYGNITE_BASE.DS.APPPATH);
        $this->generateForm();

        $this->info("Form ".APPPATH."/Form/".Inflector::classify($this->tableName)."Form".EXT." Generated Successfully By Cygnite Cli.");
    }

    /**
     * We will get all column schema from database
     * @return mixed
     */
    private function getColumns()
    {
        $table = $this->table()->connect($this->database, Inflector::tabilize($this->tableName));

        return $table->{__FUNCTION__}();
    }

    /**
     * We will generate Form
     */
    private function generateForm()
    {
        // Generate Form Component class
        $controllerInstance = Controller::instance($this->columns, $this->viewType, $this);

        $formTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Form'.DS;

        $form = new Form($controllerInstance, $this);
        $form->setFormTemplatePath($formTemplateDir);

        $form->generate();
    }
}
