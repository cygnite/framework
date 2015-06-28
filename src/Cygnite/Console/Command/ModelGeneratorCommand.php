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

use Cygnite\Database\Table;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Database\Connection;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Command\Command;
use Cygnite\Database\Exceptions\DatabaseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Cygnite Model Generator
 *
 * This class used to generate model skeleton
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class ModelGeneratorCommand extends Command
{
    public $applicationDir;

    public $model;

    public $inflection;

    public $table;

    public $controller;

    public $database;

    private $columns;

    protected $name = 'model:create';

    protected $description = 'Generate Sample Model Class Using Cygnite CLI';

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

    public function getModel()
    {
        return $this->model;
    }

    /**
     * We will get all column schema from database
     * @return mixed
     */
    private function getColumns()
    {
        $table = $this->table()->connect($this->database, Inflector::tabilize($this->model));

        return $table->{__FUNCTION__}();
    }

    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name Of Your Model Class ?')
             ->addArgument('database', InputArgument::OPTIONAL, '');
    }

    /**
     * We will execute the crud command and generate files
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input)->setOutput($output);

        // Your model name
        $this->model = Inflector::classify($input->getArgument('name'));
        /*
         | Check for argument database name if not given
         | we will use default database connection
         */
        $this->database = $this->getDatabase();
        $this->columns = $this->getColumns();

        if (empty($this->columns)) {
            throw new \Exception("Please check your model name. It seems table doesn't exists into database.");
        }

        $this->applicationDir = CYGNITE_BASE.DS.APPPATH;
        $this->generateModel();

        $modelPath = $this->applicationDir.DS.'models'.DS.$this->model.EXT;
        $output->writeln("Model $this->model generated successfully into ".$modelPath);
    }

    /**
     * Get Database name
     * @return mixed
     */
    public function getDatabase()
    {
        return ($this->input->getArgument('database') != '') ?
            $this->input->getArgument('database') :
            Connection::getDefaultConnection();
    }

    /**
     * We will generate model here
     */
    private function generateModel()
    {
        $modelInstance = Model::instance($this);
        $modelTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('models').DS;

        $modelInstance->setModelTemplatePath($modelTemplateDir);
        $modelInstance->updateTemplate();
        $modelInstance->generate();

        return true;
    }
}
