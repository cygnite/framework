<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database\Table;

use Cygnite\Database\ConnectionManagerTrait;

class Table
{
    use ConnectionManagerTrait;

    private $_connection;

    private $schemaInstance;

    public $database;

    public $tableName;

    protected $primaryKey;

    private $query;

    private $statement;

    /**
     * @param $database
     * @param $model
     * @return $this
     */
    public function connect($database, $model)
    {
        $this->database = $database;
        $this->tableName = $model;
        $this->_connection = $this->getConnection($database);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultDatabaseConnection()
    {
        return $this->getDefaultConnection();
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        $conn = null;
        $conn = $this->_connection;
        $schema = Schema::instance(
            $this,
            function ($table) {
                $table->tableName = $this->tableName;
                $columns = null;
                //$table->setDbConnection($this->_connection, $this->database);
                $table->setTableSchema();

                return $table->schema;
            }
        );

        $columns = $this->query($schema)->getAll();
        $this->setSchemaInstance($columns);

        return $columns;
    }

    /**
     * Set Schema Instance
     * @param $instance
     */
    public function setSchemaInstance($instance)
    {
        $this->schemaInstance = $instance;
    }

    /**
     * Get schema instance
     *
     * @return null
     */
    public function getSchemaInstance()
    {
        return !is_null($this->schemaInstance) ? $this->schemaInstance : null;
    }


    /**
     * @param null $queryString
     * @return $this
     */
    private function query($queryString = null)
    {
        $query = ($queryString == null) ? $this->query : $queryString;

        $this->statement = $this->_connection->prepare($query);
        $this->statement->execute();

        return $this;
    }

    public function getAll()
    {
        return $this->statement->fetchAll();
    }

    /**
     * @param string $tableName
     */
    public function makeMigration($tableName = 'migrations')
    {
        $this->connect(
            trim($this->getDefaultDatabaseConnection()),
            $tableName
        );

        //Create migration table in order to save migrations information
        Schema::make($this,
            function ($table) use ($tableName) {
                $table->tableName = $tableName;
                $table->database = trim($this->getDefaultDatabaseConnection());
                $table->create(
                    array(
                        array('column'=> 'id', 'type' => 'int', 'length' => 11,
                            'increment' => true, 'key' => 'primary'),
                        array('column'=> 'migration', 'type' => 'string', 'length' =>255),
                        array('column'=> 'version', 'type' => 'int'),
                        array('column'=> 'created_at',  'type' => 'datetime')
                    ),
                    'InnoDB',
                    'latin1'
                )->run();
            }
        );
    }

    /**
     * @param $migration
     * @return mixed
     */
    public function updateMigrationVersion($migration)
    {
        $date = new \DateTime("now");

        $date->setTimezone(new \DateTimeZone(SET_TIME_ZONE));

        $migrationName = $migration->getVersion().$migration->getMigrationClass();

        $this->connect(
            trim($this->getDefaultDatabaseConnection()),
            'migrations'
        );

        $sql = "INSERT INTO migrations (`migration`,  `created_at`)
                VALUES('".$migrationName."',
                          '".$date->format('Y-m-d H:i:s')."'
                      )";

        return $this->_connection->prepare($sql)->execute();
    }
}
