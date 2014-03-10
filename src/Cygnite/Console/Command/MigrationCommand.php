<?php
namespace Cygnite\Console\Command;

use Cygnite\Console\Generator\Migrator;
use Cygnite\Cygnite;
use Cygnite\Inflector;
use Cygnite\Database;
use Cygnite\Database\Table;
use Cygnite\Database\Schema;
use Cygnite\Console\Generator\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;


class MigrationCommand extends Command
{
    private $name = 'migrate';

    private $table;

    private $migrationDir;

    protected function configure()
    {
        //cygnite migrate:init
        $this->setName($this->name)
             ->setDescription('Migrate database By Cygnite CLI')
			 //->addArgument('name', null, InputArgument::OPTIONAL, 'Migration Name ?')
             //->addArgument('version',null, InputArgument::OPTIONAL, 'Your migration version')
             ->addArgument('type', null, InputArgument::OPTIONAL, '')
             //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Migrate init - to create migration table and
        // migrate create_users_table to create class and then you write schema to create
        // Then When you run "migrate run" it will make ur schema up
        // To rollback changes - migrate create_user_table --version 1232344 -1
        //$name = $input->getArgument('name');
        //$version = $input->getArgument('version');
        $type = $input->getArgument('type');
		//$output->writeln($name);
		//$output->writeln($version);
        //$output->writeln($yell);

        $migration = $migrationName = null;

        $migration = Migrator::instance(new Inflector, $this);
        $migration->getLatestMigration($this->migrationDir)
                  ->setMigrationClassName();

        if ($type == '') {
            $migration->updateMigration();
        } else {
            $migration->updateMigration('down');
        }
    }

    public function setSchema(Table $table)
    {
        if ($table instanceof Table) {
            $this->table = $table;
        }

    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self();
        }
    }

    public function setMigrationPath($dir)
    {
        $this->migrationDir = $dir.DS.'database'.DS.'migrations'.DS;
    }

}
