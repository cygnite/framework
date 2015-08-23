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
use Cygnite\Console\Command\Command;
use Apps\Resources\Database\Seeding\DatabaseTable;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Cygnite Seeder Command
 *
 * Migration Command class used to take care of your database migrations using Cygnite CLI.
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class SeederCommand extends Command
{
    /**
     * Name of your console command
     *
     * @var string
     */
    protected $name = 'database:seed';

    /**
     * Description of your console command
     *
     * @var string
     */
    protected $description = 'Seed Database By Cygnite CLI';

    /**
     * Console command arguments
     *
     * @var array
     */
    protected $arguments = [
        ['name', InputArgument::OPTIONAL, null],
    ];

    /**
     * @var \Apps\Resources\Database\Seeding\DatabaseTable
     */
    private $seeder;

    /**
     * @param DatabaseTable $seeder
     * @throws \InvalidArgumentException
     */
    public function __construct(DatabaseTable $seeder)
    {
        parent::__construct();

        if (!$seeder instanceof DatabaseTable) {
            throw new \InvalidArgumentException(sprintf('Constructor expects instance of DatabaseTable, givem %s.', $seeder));
        }

        $this->seeder = $seeder;
    }

    /**
     * @return DatabaseTable
     */
    public function seeder()
    {
        return $this->seeder;
    }

    /**
     * Execute the command
     *
     * @return int|null|void
     */
    public function process()
    {
        // Migrate init - to create migration table
        $name = $this->argument('name');

        if (!is_null($name)) {
            if (string_has($name, ',')) {
                $this->seeder()->executeOnly($name);
            } else {
                $this->seeder()->executeOnly($name);
            }
        }

        $this->seeder()->run();

        $this->info("Seeding Completed Successfully!");
    }

    /**
     * Seeder directory path
     *
     * @return string
     */
    public function getSeederPath()
    {
        return realpath(CYGNITE_BASE.DS.APPPATH.DS.'Resources'.DS.'Database'.DS.'Seeding').DS;
    }
}
