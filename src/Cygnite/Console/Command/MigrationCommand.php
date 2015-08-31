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
use Cygnite\Console\Generator\Migrator;
use Cygnite\Console\Generator\Controller;
use Cygnite\Console\Command\Command;
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
    /**
     * Name of your console command
     *
     * @var string
     */
    protected $name = 'migrate';

    /**
     * Description of your console command
     *
     * @var string
     */
    protected $description = 'Migrate Database By Cygnite CLI';

    /**
     * Console command arguments
     *
     * @var array
     */
    protected $arguments = [
        ['type', null, InputArgument::OPTIONAL, ''],
    ];

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
            throw new \InvalidArgumentException(sprintf('Constructor expects instance of Table, given %s.', $table));
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
     * Execute Command To Run Migration
     *
     * @return mixed|void
     */
    public function process()
    {
        // Migrate init - to create migration table
        $type = $this->argument('type');

        $migration = Migrator::instance($this);
        $migration->getLatestMigration()
                  ->setMigrationClassName();

        if ($type == '' || $type == 'up') {
            $migration->updateMigration();
        } else {
            $migration->updateMigration('down');
        }

        $this->info("Migration Completed Successfully!");
    }

    /**
     * Get Migration Path
     *
     * @return string
     */
    public function getMigrationPath()
    {
        return realpath(CYGNITE_BASE.DS.APPPATH.DS.'Resources'.DS.'Database'.DS.'Migrations').DS;
    }
}
