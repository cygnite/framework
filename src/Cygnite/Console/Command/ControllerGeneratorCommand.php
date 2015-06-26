<?php
namespace Cygnite\Console\Command;

use Cygnite\Helpers\Inflector;
use Cygnite\Foundation\Application;
use Cygnite\Console\Generator\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ControllerGeneratorCommand extends Command
{
    public $applicationDir;

    public $controller;

    private $isResourceController;

    public $inflection;

    public static function make()
    {
        return new ControllerGeneratorCommand();
    }

    protected function configure()
    {
        $this->setName('controller:create')
             ->setDescription('Generate Sample Controller Using Cygnite CLI')
             ->addArgument('name', InputArgument::OPTIONAL, 'Your Controller Name ?')
             ->addOption('resource', null, InputOption::VALUE_NONE, 'If set, will create RESTful resource controller.')
        ;
    }

    /**
     * We will execute the controller command and generate classes
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Your controller name
        $this->controller = Inflector::classify($input->getArgument('name')).'Controller';

        // By default we will generate basic controller, if resource set then we will generate
        // REST-ful Resource controller
        $this->setControllerType($input);

        try {
            $this->makeController();
        } catch (\Exception $e) {
            throw $e;
        }

        $output->writeln('<info>Controller '.$this->controller.' Generated Successfully By Cygnite Cli.</info>');
    }

    /**
     * @param $input
     */
    private function setControllerType($input)
    {
        $this->isResourceController = ($input->getOption('resource')) ? true : false;
    }

    /**
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
