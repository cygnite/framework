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

use Cygnite\Database\Table;
use Cygnite\Helpers\Inflector;
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
    protected $name = 'migrate';

    protected $description = 'Migrate Database By Cygnite CLI';

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

    public function table()
    {
        return $this->table;
    }

    protected function configure()
    {
        $this->addArgument('type', null, InputArgument::OPTIONAL, '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input)->setOutput($output);

        // Migrate init - to create migration table
        $type = $input->getArgument('type');

        $migration = Migrator::instance($this);
        $migration->getLatestMigration($this->getMigrationPath())
                  ->setMigrationClassName();

        if ($type == '' || $type == 'up') {
            $migration->updateMigration();
        } else {
            $migration->updateMigration('down');
        }

        $this->info("Migration completed Successfully!");
    }

    public function getMigrationPath()
    {
        $path = CYGNITE_BASE.DS.APPPATH.DS.'Resources'.DS.'Database'.DS.'Migrations'.DS;
        return $path;
    }
}
