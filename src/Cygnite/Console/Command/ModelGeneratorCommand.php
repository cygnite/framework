<?php
namespace Cygnite\Console\Command;

use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Database\Connection;
use Cygnite\Console\Generator\Model;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ModelGeneratorCommand extends Command
{
    public $applicationDir;

    public $model;

    public $inflection;

    private $tableSchema;

    public $controller;

    public $database;

    private $columns;

    public static function make()
    {
        return new ModelGeneratorCommand();
    }

    public function setSchema($table)
    {
        $this->tableSchema = $table;
    }

    /**
     * We will get all column schema from database
     * @return mixed
     */
    private function getColumns()
    {
        return $this->tableSchema->connect(
                    $this->database,
                    $this->inflection->tabilize($this->model)
                )->getColumns();
    }

    /**
     * Get primary key of the table
     * @return null
     */
    public function getPrimaryKey()
    {
        $primaryKey = null;

        if (count($this->columns) > 0) {
            foreach ($this->columns as $key => $value) {
                if ($value->column_key == 'PRI' || $value->extra == 'auto_increment') {
                    $primaryKey = $value->column_name;
                    break;
                }
            }
        }

        return $primaryKey;
    }

    protected function configure()
    {
        $this->setName('model:create')
             ->setDescription('Generate Sample Model Class Using Cygnite CLI')
             ->addArgument('name', InputArgument::OPTIONAL, 'Name Of Your Model Class ?')
             ->addArgument('database', InputArgument::OPTIONAL, 'If not specified we will take default database as connection.')
        ;
    }

    /**
     * We will execute the crud command and generate files
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inflection = new Inflector();
        // Your model name
        $this->model = $this->inflection->classify($input->getArgument('name'));

        // Check for argument database name if not given we will use default
        // database connection
        $this->database = $this->getDatabase($input);

        $this->columns = $this->getColumns();

        if (empty($this->columns)) {
            throw new \Exception("Please check your model name. It seems doesn't exists in the database.");
        }

        $this->applicationDir = BASE_PATH.DS.APP_PATH;
        $this->generateModel();

        $modelPath = $this->applicationDir.DS.'models'.DS.$this->model.EXT;
        $output->writeln("Model $this->model generated successfully into ".$modelPath);
    }

    /**
     * @param $input
     * @return mixed
     */
    private function getDatabase($input)
    {
        return ($input->getArgument('database') != '') ?
            $input->getArgument('database') :
            Connection::getDefaultConnection();
    }

    /**
     * We will generate model here
     */
    private function generateModel()
    {
        $modelInstance = Model::instance($this->inflection, $this);
        $modelTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.ucfirst('models').DS;

        $modelInstance->setModelTemplatePath($modelTemplateDir);
        $modelInstance->updateTemplate();
        $modelInstance->generate();
        return true;
    }
}
