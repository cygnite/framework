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

use Cygnite\Database;
use Cygnite\Database\Table;
use Cygnite\Database\Schema;
use Cygnite\Helpers\Inflector;
use Cygnite\Foundation\Application;
use Cygnite\Console\Generator\Migrator;
use Cygnite\Console\Generator\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Cygnite Migration Command
 *
 * Migration Command class used to take care of your database migrations using Cygnite CLI.
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class MigrationCommand extends Command
{
    private $name = 'migrate';

    public $table;

    private $migrationDir;

    protected function configure()
    {
        //cygnite migrate:init
        $this->setName($this->name)
             ->setDescription('Migrate database By Cygnite CLI')
             ->addArgument('type', null, InputArgument::OPTIONAL, '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Migrate init - to create migration table
        $type = $input->getArgument('type');

        $migration = $migrationName = null;

        $migration = Migrator::instance($this);
        $migration->getLatestMigration($this->migrationDir)
                  ->setMigrationClassName();

        if ($type == '' || $type == 'up') {
            $migration->updateMigration();
        } else {
            $migration->updateMigration('down');
        }
        $output->writeln("Migration completed Successfully!");
    }

    public function setSchema(Table $table)
    {
        if ($table instanceof Table) {
            $this->table = $table;
        }

    }

    public static function __callStatic($method, $arguments = [])
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
