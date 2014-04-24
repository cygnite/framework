<?php
namespace Cygnite\Console\Command;

use Cygnite\Cygnite;
use Cygnite\Database\Table;
use Cygnite\Inflector;
use Cygnite\Console\Generator\Migrator;
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
 * @Package               :  Console
 * @Filename             :  InitCommand.php
 * @Description        :  Init Command class used to generate your migration file using Cygnite CLI. 
 *                                         Cygnite Cli driven by Symfony2 Console Component. 
 * @Author                :  Sanjoy Dey
 * @Copyright         :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	             :  Version 1.0.6
 * @File Source
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
            ->addArgument('name', null, InputArgument::OPTIONAL, 'Migration Name ?')
            ->setHelp("<<<EOT
                The <info>init</info> command creates a skeleton file and a migrations directory
                <info>cygnite migrate:init</info>
                EOT>>>"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input->getArgument('name');
        $this->appDir = BASE_PATH.DS.APP_PATH;
        $migrateTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'apps'.DS.'database'.DS;

        $migrateInstance = null;
        $migrateInstance = Migrator::instance(new Inflector, $this);
        $this->table->makeMigration('migrations');
        $migrateInstance->setTemplateDir($migrateTemplateDir);
        $migrateInstance->replaceTemplateByInput();
        $status = $migrateInstance->generate(new \DateTime('now', new \DateTimeZone('Europe/London')));

        if ($status) {
            $output->writeln("Your migration class generated in $status");
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
}
