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

use Cygnite\Console\Generator\Controller;
use Cygnite\Console\Generator\Form;
use Cygnite\Console\Generator\View;
use Cygnite\Database\Schema;
use Cygnite\Database\Table\Table;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Symfony\Component\Console\Input\InputArgument;

/*
 * Form Generator Command
 *
 * Form generator command class used to generate form using Cygnite CLI.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class FormGeneratorCommand extends Command
{
    /**
     * Name of your console command.
     *
     * @var string
     */
    protected $name = 'form:create';

    /**
     * Description of your console command.
     *
     * @var string
     */
    protected $description = 'Generate Form Class Using Cygnite CLI';

    /**
     * Console command arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['name', InputArgument::OPTIONAL, ''],
        ['database', InputArgument::OPTIONAL, ''],
    ];

    /**
     * @var Application Directory Path
     */
    public $applicationDir;

    /**
     * @var \Cygnite\Database\Table\Table
     */
    public $table;

    /**
     * @var Table Name
     */
    public $tableName;

    /**
     * @var Database connection name
     */
    public $database;
    /**
     * @var Table Columns
     */
    public $columns;

    /**
     * @param Table $table
     *
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

    /**
     * @return Table
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * We will execute the crud command and generate files.
     *
     * @return int|null|void
     */
    public function process()
    {
        /*
         | Check for argument database name if not given we will use default
         |  database connection
         */
        $this->database = (!is_null($this->argument('database'))) ?
            $this->argument('database') :
            $this->table()->getDefaultDatabaseConnection();

        $this->tableName = (!is_null($this->argument('name'))) ?
            $this->argument('name') :
            'Form';

        $this->columns = $this->getColumns();
        $this->applicationDir = realpath(CYGNITE_BASE.DS.APPPATH);
        $this->generateForm();

        $this->info('Form '.APPPATH.'/Form/'.Inflector::classify($this->tableName).'Form'.EXT.' Generated Successfully!.');
    }

    /**
     * We will get all column schema from database.
     *
     * @return mixed
     */
    private function getColumns()
    {
        $table = $this->table->connect($this->database, Inflector::tabilize($this->tableName));

        return $table->getColumns();
    }

    /**
     * We will generate Form.
     */
    private function generateForm()
    {
        // Generate Form Component class
        $controllerInstance = Controller::instance($this->columns, null, $this);

        $formTemplateDir =
            dirname(dirname(__FILE__)).DS.'src'.DS.'Apps'.DS.'Form'.DS;

        $form = new Form($controllerInstance, $this);
        $form->setFormTemplatePath($formTemplateDir);

        $form->generate();
    }
}
