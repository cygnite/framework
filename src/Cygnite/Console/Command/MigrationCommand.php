<?php
namespace Cygnite\Console\Command;

use Cygnite\Console\Generator\Migrator;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
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

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so that I can send you a copy immediately.
 *
 * @Package            :  Console
 * @Filename           :  MigrationCommand.php
 * @Description        :  Migration Command class used to take care of your database migrations using Cygnite CLI.
 *                        Cygnite Cli driven by Symfony2 Console Component.
 * @Author             :  Sanjoy Dey
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0.6
 * @File Source
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

        $migration = Migrator::instance(new Inflector, $this);
        $migration->getLatestMigration($this->migrationDir)
                  ->setMigrationClassName();

        if ($type == '') {
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
