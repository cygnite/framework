<?php
namespace Cygnite\Database;

use Cygnite\Database\Query\Builder as QueryBuilder;
use Cygnite\Helpers\Inflector;

/**
 * Class DB
 *
 * @package Cygnite\Database
 */
class DB
{
    protected $database;

    protected $tableName;

    // Default primary key
    public static $defaultPrimaryKey = 'id';

    //Holds primary key
    protected $primaryKey;
    protected $primaryKeyValue;

    /**
     * Get DB instance
     *
     * @param callable $callback
     * @return static
     */
    public static function make(\Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new static());
        }

        return new static();
    }

    /**
     * Configure parameters
     *
     * @param        $table
     * @param string $connection
     * @return QueryBuilder
     */
    public function setup($table, $connection = 'default')
    {
        $query = $this->query();
        $this->setDatabase($connection == 'default' ? $query->getDefaultConnection() : $connection);
        $this->setTableName($table);
        $query->setActiveRecord($this);

        return $query;
    }

    /**
     * Get Query instance
     *
     * @param        $table
     * @param string $connection
     * @return QueryBuilder
     */
    public static function table($table, $connection = 'default')
    {
        return static::make()->setup($table, $connection);
    }

    /**
     * Return Query Builder Instance
     *
     * @return QueryBuilder
     */
    public static function query()
    {
        QueryBuilder::$dataSource = true;

        return new QueryBuilder();
    }

    /**
     * Set table name to run queries
     *
     * @param $table
     * @return $this
     */
    public function setTableName($table)
    {
        $this->tableName = $table;

        return $this;
    }

    /**
     * Get Table name
     *
     * @return mixed
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * This method won't be used though
     * @return string
     */
    public function getModelClassNs()
    {
        return ;
    }

    /**
     * Set database name to retrieve connection object
     *
     * @param $database
     * @return $this
     */
    public function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get database name
     *
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Set the primary key
     *
     */
    protected function setPrimaryKey($key)
    {
        $this->primaryKey = !is_null($key) ? $key : static::$defaultPrimaryKey;

        return $this;
    }

    /**
     * Get the primary key of table
     *
     * @return null|string
     */
    public function getKeyName()
    {
        return isset($this->primaryKey) ? $this->primaryKey : static::$defaultPrimaryKey;
    }
}
