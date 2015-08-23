<?php
namespace Cygnite\Console\Command;

use Cygnite\Database\Table\Table;
use Cygnite\Helpers\Inflector;
use Cygnite\Foundation\Application;
use Cygnite\Console\Generator\Controller;
use Cygnite\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ControllerGeneratorCommand extends Command
{
    /**
     * Name of your console command
     *
     * @var string
     */
    protected $name = 'controller:create';

    /**
     * Description of your console command
     *
     * @var string
     */
    protected $description = 'Generate Sample Controller Using Cygnite CLI';

    /**
     * Console command arguments
     *
     * @var array
     */
    protected $arguments = [
        ['name', InputArgument::OPTIONAL, 'Your Controller Name ?'],
    ];

    /**
     * @var array
     */
    protected $options = [
        ['resource', null, InputOption::VALUE_NONE, 'If set, will create RESTful resource controller.']
    ];

    /**
     * @var Application directory path
     */
    public $applicationDir;

    /**
     * @var Controller name
     */
    public $controller;

    /**
     * @var Controller type
     */
    private $isResourceController;

    /**
     * @var \Cygnite\Database\Table\Table
     */
    public $table;

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
     * We will execute the controller command and generate classes
     *
     * @return mixed|void
     * @throws \Exception
     */
    public function process()
    {
        // Your controller name
        $this->controller = Inflector::classify($this->argument('name')).'Controller';

        // By default we will generate basic controller, if resource set then we will generate
        // REST-ful Resource controller
        $this->setControllerType();

        try {
            $this->makeController();
        } catch (\Exception $e) {
            throw $e;
        }

        $this->info('Controller '.$this->controller.' Generated Successfully By Cygnite Cli.');
    }

    /**
     * Set controller type
     *
     */
    private function setControllerType()
    {
        $this->isResourceController = ($this->option('resource')) ? true : false;
    }

    /**
     * Get controller type either normal controller or resource controller
     *
     * @return null
     */
    public function getControllerType()
    {
        return (isset($this->isResourceController)) ? $this->isResourceController : null;
    }

    /**
     * @return mixed
     */
    private function makeController()
    {
        $controller = null;
        // Create Controller instance
        $controller = Controller::instance([], null, $this);
        $resourcePath = 'Resources'.DS.'Stubs'.DS;
        $controllerTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.ucfirst('apps').DS.'Controllers'.DS.$resourcePath;

        $controller->setControllerTemplatePath($controllerTemplateDir);
        $controller->setApplicationDirectory(CYGNITE_BASE.DS.APPPATH);
        $controller->setControllerName($this->controller);
        return $controller->{__FUNCTION__}();
    }
}
