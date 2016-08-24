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

use Cygnite\Console\Generator\Controller;
use Cygnite\Console\Generator\Model;
use Cygnite\Console\Generator\View;
use Cygnite\Database\Table\Table;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GeneratorCommand extends Command
{
    /**
     * Name of your console command.
     *
     * @var string
     */
    protected $name = 'generate:crud';

    /**
     * Description of your console command.
     *
     * @var string
     */
    protected $description = 'Generate Sample Crud Application Using Cygnite CLI';

    /**
     * Console command arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['name', InputArgument::OPTIONAL, 'Your Controller Name ?'],
        ['model', InputArgument::OPTIONAL, 'Your Model Name ?'],
        ['database', InputArgument::OPTIONAL, ''],
    ];

    /**
     * @var array
     */
    protected $options = [
        ['template', null, InputOption::VALUE_NONE, 'If set, will use twig template for view page.'],
    ];

    /**
     * @var Application Directory Path
     */
    public $applicationDir;

    /**
     * @var Controller Name
     */
    public $controller;

    /**
     * @var Model Class Name
     */
    public $model;

    /**
     * @var Database Connection Name
     */
    protected $database;

    /**
     * @var \Cygnite\Database\Table\Table
     */
    protected $table;

    /**
     * @var Table Columns
     */
    private $columns;

    /**
     * @var Type Of View Page
     */
    private $viewType;

    /**
     * @param Table $table
     *
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
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param $app
     */
    public function setCygnite($app)
    {
        $this->cygnite = $app;
    }

    /**
     * We will execute the crud command and generate files.
     *
     *
     * @return int|null|void
     */
    public function process()
    {
        // Your controller name
        $this->controller = Inflector::classify($this->argument('name')).'Controller';
        // Model name
        $this->model = Inflector::classify($this->argument('model'));
        /*
         | Check for argument database name if not given we will use default
         |  database connection
         */
        $this->database = (!is_null($this->argument('database'))) ?
            $this->argument('database') :
            $this->table()->getDefaultDatabaseConnection();

        // By default we will generate plain php layout and view pages
        $this->viewType = ($this->option('template') == false) ? 'php' : 'twig';
        $this->columns = $this->getColumns();

        if (empty($this->columns)) {
            exit($this->error("Please provide valid table name. It seems doesn't exists in the database."));
        }

        $this->applicationDir = CYGNITE_BASE.DS.APPPATH;

        $this->generateController();
        $this->generateModel();
        $this->generateViews();

        $controller = str_replace('Controller', '', $this->controller);

        $this->comment("\n".'You need to route the controller to access methods from browser, add
        $this->routesController->controller("'.$controller.'"); inside the method '."\n".'
        \Apps\Routing\RouteCollection::executeStaticRoutes();'."\n");

        $this->info('Crud Generated Successfully By Cygnite Cli.');
    }

    /**
     * We will get all column schema from database.
     *
     * @return mixed
     */
    public function getColumns()
    {
        $table = $this->table->connect($this->database, Inflector::tabilize($this->model));

        return $table->{__FUNCTION__}();
    }

    /**
     * We will generate Controller.
     */
    private function generateController()
    {
        // Generate Controller class
        $controllerInstance = Controller::instance($this->columns, $this->viewType, $this);

        $controllerTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Controllers'.DS;

        $controllerInstance->setControllerTemplatePath($controllerTemplateDir);
        $controllerInstance->setApplicationDirectory($this->applicationDir);

        $controllerInstance->setControllerName($this->controller);
        $controllerInstance->setModelName($this->model);
        $controllerInstance->updateTemplate();
        $controllerInstance->generateControllerTemplate();

        $controllerInstance->generate();

        $this->info("Controller $this->controller Generated Successfully!");
    }

    /**
     * We will generate model here.
     */
    private function generateModel()
    {
        $modelInstance = Model::instance($this);
        $modelTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Models'.DS;

        $modelInstance->setModelTemplatePath($modelTemplateDir);
        $modelInstance->updateTemplate();
        $modelInstance->generate();
        $this->info("Model $this->model Generated Successfully!");
    }

    /**
     * We will generate the view pages into views directory.
     */
    private function generateViews()
    {
        $viewInstance = View::instance($this);
        $viewInstance->setLayoutType($this->viewType);
        $viewTemplateDir = dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Views'.DS;
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
            'Views Generated In '.str_replace('Controller', '', $this->controller).' Directory..'
        );
    }
}
