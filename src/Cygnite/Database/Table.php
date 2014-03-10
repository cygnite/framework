<?php
namespace Cygnite\Database;

use Cygnite\Database\Connections;

class Table extends Connections
{

    private $_connection;

    private $schemaInstance;

    public $database;

    public $tableName;

    protected $primaryKey;

    private $query;

    private $prepareQuery;

    public function connect($database, $model)
    {
        $this->database = $database;
        $this->tableName = $model;
        $this->_connection = $this->getConnection($database);

        return $this;
    }

    public function getDefaultDatabaseConnection()
    {
        return $this->getDefaultConnection();
    }

    public function getColumns()
    {
        $conn = null;
        $conn = $this->_connection;
        Schema::getInstance(
            $this,
            function($table) {
                $table->tableName = $this->tableName;
                $columns = null;
                //$table->setDbConnection($this->_connection, $this->database);
                $table->setTableSchema();
                //$columns = $conn->query($table->schema)->fetchAll();
                $columns = $this->query($table->schema)->getAll();

                $this->schemaInstance = $columns;
            }
        );

        return $this->schemaInstance;
    }

    private function query($queryString = null)
    {
        $query = ($queryString == null) ? $this->query : $queryString;

        $this->prepareQuery = $this->_connection->query($query);

        return $this;
    }

    public function getAll()
    {
        return $this->prepareQuery->fetchAll();
    }

    public function makeMigration($tableName = 'migrations')
    {
        $this->connect(
            trim(Connections::getDefaultConnection()),
            $tableName
        );

        //Create migration table in order to save migrations information
        Schema::getInstance($this,
            function($table) use ($tableName){
                $table->tableName = $tableName;
                $table->database = trim($this->getDefaultConnection());
                $table->create(
                    array(
                        array('name'=> 'id', 'type' => 'int', 'length' => 11,
                            'increment' => true, 'key' => 'primary'),
                        array('name'=> 'migration', 'type' => 'string', 'length' =>255),
                        array('name'=> 'version', 'type' => 'int', 'length' =>11),
                    ),
                    'InnoDB',
                    'latin1'
                )->run();
            }
        );
    }
}
