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

use Cygnite\Database\Table\Table;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Command\Command;
use Cygnite\Database\ConnectionManagerTrait;
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
    use ConnectionManagerTrait;

    /**
     * Name of your console command
     *
     * @var string
     */
    protected $name = 'model:create';

    /**
     * Description of your console command
     *
     * @var string
     */
    protected $description = 'Generate Sample Model Class Using Cygnite CLI';

    /**
     * Console command arguments
     *
     * @var array
     */
    protected $arguments = [
        ['name', InputArgument::OPTIONAL, 'Name Of Your Model Class ?'],
        ['database', InputArgument::OPTIONAL, ''],
    ];

    public $applicationDir;

    /**
     * @var Name of model class
     */
    public $model;

    /**
     * @var \Cygnite\Database\Table\Table
     */
    public $table;

    /**
     * @var Database connection name
     */
    public $database;

    /**
     * @var Set Columns name
     */
    private $columns;

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

    /**
     * @return Table
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @return Name of model class
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * We will get all column schema from database
     * @return mixed
     */
    public function getColumns()
    {
        $table = $this->table()->connect($this->database, Inflector::tabilize($this->model));

        return $table->{__FUNCTION__}();
    }

    /**
     * We will execute the command and generate model class
     *
     * @return int|null|void
     */
    public function process()
    {
        $table = $this->argument('name');
        // Your model name
        $this->model = Inflector::classify($table);
        /*
         | Check for argument database name if not given
         | we will use default database connection
         */
        $this->database = $this->getDatabase();
        $this->columns = $this->getColumns();

        if (empty($this->columns)) {
            exit($this->error("Please check your model name. It seems table '$table' doesn't exists!"));
        }

        $this->applicationDir = CYGNITE_BASE.DS.APPPATH;
        $this->generateModel();

        $modelPath = APPPATH.DS.'Models'.DS.$this->model.EXT;

        $this->info("Model $this->model Generated Successfully Into ".$modelPath);
    }

    /**
     * Get Database name
     * @return mixed
     */
    public function getDatabase()
    {
        return ($this->argument('database') != '') ?
            $this->argument('database') :
            $this->getDefaultConnection();
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
