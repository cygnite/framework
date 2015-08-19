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

use Cygnite\Helpers\Inflector;
use Cygnite\Database\Table\Table;
use Cygnite\Console\Generator\View;
use Cygnite\Foundation\Application;
use Cygnite\Console\Command\Command;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Generator\Controller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class GeneratorCommand extends Command
{
    public $applicationDir;
    public $controller;
    public $model;
    protected $database;
    protected $table;
    protected $name = 'generate:crud';
    protected $description = 'Generate Sample Crud Application Using Cygnite CLI';
    private $columns;
    private $viewType;

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

    public function getName()
    {
        return $this->name;
    }

    public function table()
    {
        return $this->table;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setCygnite($app)
    {
        $this->cygnite = $app;
    }

    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Your Controller Name ?')
            ->addArgument('model', InputArgument::OPTIONAL, 'Your Model Name ?')
            ->addArgument('database', InputArgument::OPTIONAL, '')
            ->addOption('template', null, InputOption::VALUE_NONE, 'If set, will use twig template for view page.');
    }

    /**
     * We will execute the crud command and generate files
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input)->setOutput($output);

        // Your controller name
        $this->controller = Inflector::classify($input->getArgument('name')) . 'Controller';
        // Model name
        $this->model = Inflector::classify($input->getArgument('model'));
        /**
         | Check for argument database name if not given we will use default
         |  database connection
         */
        $this->database = (!is_null($input->getArgument('database'))) ?
            $input->getArgument('database') :
            $this->table->getDefaultDatabaseConnection();

        // By default we will generate plain php layout and view pages
        $this->viewType = ($input->getOption('template') == false) ? 'php' : 'twig';
        $this->columns = $this->getColumns();

        if (empty($this->columns)) {
            throw new \Exception("Please provide valid table name. It seems doesn't exists in the database.");
        }

        $this->applicationDir = CYGNITE_BASE . DS . APPPATH;

        $this->generateController();
        $this->generateModel();
        $this->generateViews();

        $this->info("Crud Generated Successfully By Cygnite Cli.");
    }

    /**
     * We will get all column schema from database
     *
     * @return mixed
     */
    public function getColumns()
    {
        $table = $this->table->connect($this->database, Inflector::tabilize($this->model));

        return $table->{__FUNCTION__}();
    }

    /**
     * We will generate Controller
     */
    private function generateController()
    {
        // Generate Controller class
        $controllerInstance = Controller::instance($this->columns, $this->viewType, $this);

        $controllerTemplateDir =
            dirname(dirname(__FILE__)) . DS . 'src' . DS . 'Apps' . DS . 'Controllers' . DS;

        $controllerInstance->setControllerTemplatePath($controllerTemplateDir);
        $controllerInstance->setApplicationDirectory($this->applicationDir);

        $controllerInstance->setControllerName($this->controller);
        $controllerInstance->setModelName($this->model);
        $controllerInstance->updateTemplate();
        $controllerInstance->generateControllerTemplate();

        $controllerInstance->generate();

        $this->info("Controller $this->controller generated successfully..");
    }

    /**
     * We will generate model here
     */
    private function generateModel()
    {
        $modelInstance = Model::instance($this);
        $modelTemplateDir =
            dirname(dirname(__FILE__)) . DS . 'src' . DS . 'Apps' . DS . 'Models' . DS;

        $modelInstance->setModelTemplatePath($modelTemplateDir);
        $modelInstance->updateTemplate();
        $modelInstance->generate();
        $this->info("Model $this->model generated successfully..");
    }

    /**
     * We will generate the view pages into views directory
     */
    private function generateViews()
    {
        $viewInstance = View::instance($this);
        $viewInstance->setLayoutType($this->viewType);
        $viewTemplateDir = dirname(dirname(__FILE__)) . DS . 'src' . DS . 'Apps' . DS . 'Views' . DS;
        $viewInstance->setTableColumns($this->columns);
        $viewInstance->setViewTemplatePath($viewTemplateDir);

        // generate twig template layout if type has set via user
        if ($this->viewType == 'php') {
            // Type not set then we will generate php layout
            $viewInstance->generateLayout('layouts');
        } else {
            $viewInstance->generateLayout('layouts.main');
        }

        $viewInstance->generateViews();

        $this->info(
            "Views generated in " . str_replace("Controller", "", $this->controller) . " directory.."
        );
    }
}
