<?php
namespace Cygnite\Console;

use Cygnite\Database;
use Cygnite\Database\Table;
use Cygnite\Console\Command\InitCommand;
use Cygnite\Console\Command\GeneratorCommand;
use Cygnite\Console\Command\MigrationCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

require '/../../'.APP_PATH.'/configs/database.php';

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

        $initInstance = InitCommand::instance();
        $initInstance->setSchema(new Table);

        $migrationInstance = MigrationCommand::instance();
        $migrationInstance->setSchema(new Table);
        $migrationInstance->setMigrationPath($this->getApplicationDirectory());

        $this->addCommands(
            array(
                $initInstance,
                $generateInstance,
                $migrationInstance,
            )
        );
    }

    private function getApplicationDirectory()
    {
        return BASE_PATH.DS.APP_PATH;
    }
}
