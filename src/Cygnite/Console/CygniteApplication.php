<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Console;

use Cygnite\Database;
use Cygnite\Database\Table;
use Cygnite\Console\Command\InitCommand;
use Cygnite\Console\Command\GeneratorCommand;
use Cygnite\Console\Command\MigrationCommand;
use Symfony\Component\Console\Application;
use Cygnite\Console\Command\FormGeneratorCommand;
use Cygnite\Console\Command\ControllerGeneratorCommand;
use Cygnite\Console\Command\ModelGeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

require CYGNITE_BASE.DS.APPPATH.'/configs/database.php';

/**
 * Cygnite Console Application
 *
 * This class is the entry point of Console component. It is the
 * a middle ware of all your console commands.
 * Cygnite CLI powered by Symfony2 Console Component.
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class CygniteApplication extends Application
{
    private $version;

    private $description;

    public function __construct($version, $description = 'Cygnite CLI Application')
    {
        parent::__construct($description, $version);

        //Get Generator command instance and set table schema
        $generateInstance = GeneratorCommand::instance();
        $generateInstance->setSchema(new Table);

        //Initialise Migration
        $initInstance = InitCommand::instance();
        $initInstance->setSchema(new Table);

        //Get the migration instance and Set schema
        $migrationInstance = MigrationCommand::instance();
        $migrationInstance->setSchema(new Table);
        $migrationInstance->setMigrationPath($this->getApplicationDirectory());

        //Get the Form Generator instance and set Schema
        $formInstance = FormGeneratorCommand::instance();
        $formInstance->setSchema(new Table);

        $controllerInstance = ControllerGeneratorCommand::make();
        $modelInstance = ModelGeneratorCommand::make();
        $modelInstance->setSchema(new Table);


        $this->addCommands(
            array(
                $initInstance,
                $generateInstance,
                $migrationInstance,
                $controllerInstance,
                $formInstance,
                $modelInstance
            )
        );
    }

    // Get the Application directory
    private function getApplicationDirectory()
    {
        return CYGNITE_BASE.DS.APPPATH;
    }
}
