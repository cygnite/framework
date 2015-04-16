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

use Cygnite\Foundation\Application;
use Cygnite\Database\Table;
use Cygnite\Helpers\Inflector;
use Cygnite\Console\Generator\Migrator;
use Symfony\Component\Console\Command\Command;
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

    private $name = 'migrate:init';

    public $input;

    public $appDir;

    private $table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->name)
            ->setDescription('Initializing Cygnite CLI..')
            ->addArgument('name', null, InputArgument::OPTIONAL, null)
            ->setHelp("<<<EOT
                The <info>init</info> command creates a skeleton file and a migrations directory
                <info>cygnite migrate:init</info>
                EOT>>>"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input->getArgument('name');
        $this->appDir = CYGNITE_BASE.DS.APPPATH;
        $migrateTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'apps'.DS.'database'.DS;

        $migrateInstance = null;
        $migrateInstance = Migrator::instance($this);
        $this->getSchema()->makeMigration('migrations');

        // We will generate migration class only if class name provided in command
        if (!is_null($this->input)) {

            $migrateInstance->setTemplateDir($migrateTemplateDir);
            $migrateInstance->replaceTemplateByInput();
            $status = $migrateInstance->generate(new \DateTime('now', new \DateTimeZone('Europe/London')));

            if ($status) {
                $output->writeln("Your migration class generated in $status");
            }
        }
    }

    /**
     * @param Table $table
     */
    public function setSchema(Table $table)
    {
        if ($table instanceof Table) {
            $this->table = $table;
        }
    }

    /**
     * @return null
     */
    public function getSchema()
    {
        return isset($this->table) ? $this->table : null;
    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self();
        }
    }
}
