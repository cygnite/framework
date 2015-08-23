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

use Cygnite\Database\Table\Table;
use Cygnite\Helpers\Inflector;
use Cygnite\Foundation\Application;
use Cygnite\Console\Generator\Migrator;
use Cygnite\Console\Command\Command;
use Cygnite\Database\ConnectionManagerTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Cygnite Migration Command
 *
 * Init Command class used to generate your migration file using Cygnite CLI.
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class InitCommand extends Command
{
    use ConnectionManagerTrait;

    /**
     * Name of your console command
     *
     * @var string
     */
    protected $name = 'migrate:init';

    /**
     * Description of your console command
     *
     * @var string
     */
    protected $description = 'Initializing Migration By Cygnite CLI';

    /**
     * Console command arguments
     *
     * @var array
     */
    protected $arguments = [
        ['name', null, InputArgument::OPTIONAL, null],
        ['database', InputArgument::OPTIONAL, ''],
    ];

    public $argumentName;

    public $appDir;

    private $table;

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

    /**
     * Set Help Message for the command
     */
    protected function configure()
    {
        $this->setHelp("<<<EOT
                The <info>init</info> command creates a skeleton file and a migrations directory
                <info>cygnite migrate:init</info>
                EOT>>>"
            );
    }

    /**
     * Execute Command To Initialize Migration Class
     *
     * @return int|null|void
     */
    public function process()
    {
        $this->argumentName = $this->argument('name');
        $this->appDir = CYGNITE_BASE.DS.APPPATH;
        $migrateTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Database'.DS;

        $migrateInstance = null;
        $migrateInstance = Migrator::instance($this);
        $this->table()->makeMigration('migrations');

        // We will generate migration class only if class name provided in command
        if (!is_null($this->argumentName)) {
            $migrateInstance->setTemplateDir($migrateTemplateDir);
            $migrateInstance->replaceTemplateByInput();
            $file = $migrateInstance->generate(new \DateTime('now', new \DateTimeZone(SET_TIME_ZONE)));

            if ($file) {
                $file = APP_NS.DS.'Resources'.DS.'Database'.DS.'Migrations'.DS.$file;
                $this->info("Your migration class generated in ".$file);
            }

            $this->info("Cool!! You are ready to use migration!");
        }
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

    /**
     * Get Database Connection Name
     *
     * @return array|mixed|string
     */
    public function getDatabaseName()
    {
        return ($this->argument('database') != '') ?
            $this->argument('database') :
            $this->getDefaultConnection();
    }
}
