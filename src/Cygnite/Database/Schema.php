<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database;

use Closure;
use Cygnite\Common\Singleton;
use Cygnite\Helpers\Inflector;
use Cygnite\Database\Connection;

/*
 * Database Schema Builder
 *
 * Build and alter your database schema on the fly.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

class Schema
{
    public $database;

    private $_pointer;

    private $inflection;

    public $primaryKey;

    protected $_connection;

    private $config;

    public $tableName;

    public $schema =array();

    private $_informationSchema = 'INFORMATION_SCHEMA';

    private $_tableSchema = 'TABLE_SCHEMA';

    const ALTER_TABLE = 'ALTER TABLE ';

    const SELECT = 'SELECT';

    /**
     * @param       $method
     * @param array $arguments
     * @return callable|void
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance' && !empty($arguments)) {

            $schema = new self($arguments[0]);

            if (is_callable(array($schema, 'init'))) {

                if ($arguments[1] instanceof Closure) {
                    return $schema->init($arguments[1], $schema);
                } else {
                    return $schema->init($schema);
                }
            }
        } else {
            throw new \Exception(
                sprintf('Oops, Undefined method called %s', 'Schema::'.$method));
        }
    }

    /**
     * You cannot create an instance of Schema class
     * directly
     *
     * @param $model
     */
    private function __construct($model)
    {
        $this->_pointer = $model;

        if (class_exists(get_class($this->_pointer))) {

            $reflectionClass = new \ReflectionClass(get_class($this->_pointer));

            if (property_exists($this->_pointer, 'database')) {
                $reflectionProperty = $reflectionClass->getProperty('database');
                $reflectionProperty->setAccessible(true);
                $this->database = $reflectionProperty->getValue($this->_pointer);
            } else {
                $this->database = Connection::getDefaultConnection();
            }


            if (property_exists($this->_pointer, 'primaryKey')) {
                $reflectionPropertyKey = $reflectionClass->getProperty('primaryKey');
                $reflectionPropertyKey->setAccessible(true);
                $this->primaryKey = $reflectionPropertyKey->getValue($this->_pointer);
            }

            $this->setConn($this->database);

            if (!property_exists($this->_pointer, 'tableName')) {
                $this->tableName = Inflector::tabilize(get_class($this->_pointer));
            }
        }

       // $this->config = Connection::getConfiguration();
    }

    /*
     * Get Schema instance to generate table schema
     * @access public
     * @param $_pointer get the model pointer
     * @param Closure instance to hold schema object
     *
     */

    public function init(Closure $callback = null, $schema = null)
    {
        if ($callback instanceof Closure) {
           return $callback($schema);
        } else {
            return $callback;
        }
    }

    public function setConn($database)
    {
        $this->_connection = Connection::getConnection($database);
    }

    public function connection()
    {
        return isset($this->_connection) ? $this->_connection : null;
    }

    public function create($columns, $engine = 'MyISAM', $charset = 'utf8')
    {
        $schema = $comma = $isNull = $tableKey = $type = "";

        $schema .= strtoupper(__FUNCTION__).' TABLE IF NOT EXISTS
        `'.$this->database.'`.`'.$this->tableName.'` (';
        $arrCount = count($columns);
        $i= 0;

        foreach ($columns as $key => $value) {

            $increment = (isset($value['increment'])) ? 'AUTO_INCREMENT' : '';
            if (isset($value['key'])) {

                $tableKey = strtoupper($value['key']).' ';

                if ($value['key'] == 'primary') {
                    $tableKey = 'PRIMARY KEY';
                }

            } else {
                $tableKey = '';
            }

            $isNull = (isset($value['null']) &&
                $value['null'] == true
            ) ? ' DEFAULT NULL' : ' NOT NULL';

            switch ($value['type']) {

                case 'int':
                    $length = (isset($value['length']) && $value['length'] !=='') ? $value['length'] : 11;
                    $type = strtoupper($value['type']).'('.$length.')';
                    break;
                case 'char':
                    $length = ($value['length'] !=='') ? $value['length'] : 2;
                    $type = strtoupper($value['type']).'('.$length.')';
                    break;
                case 'string':
                    $length = ($value['length'] !=='') ? $value['length'] : 200;
                    $type = 'VARCHAR('.$length.')';
                    break;
                case 'text':
                    $type = 'TEXT';
                    break;
                case 'longtext':
                    $type = $value['type'];
                    break;
                case 'float':
                    $length = ($value['length'] !=='') ? $value['length'] : '10,2';
                    $type = strtoupper($value['type']).'('.$length.')';
                    break;
                case 'decimal':
                    $length = ($value['length'] !=='') ? $value['length'] : '10,2';
                    $type = strtoupper($value['type']).'('.$length.')';
                    break;
                case 'enum':
                    $length = implode('","', $value['length']);
                    $type = strtoupper($value['type']).'("'.$length.'")';
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $length = $value['length'];
                    $type = strtoupper($value['type']).' ' .$length;
                    break;
                case 'time':
                    $type = 'time';
                    break;
                case 'timestamp':
                    $length = $value['length'];
                    $type = strtoupper($value['type']).' ' .$length;
                    break;
            }

            $comma =  ($i < $arrCount-1) ? ',' : '';

            $schema .= '`'.$value['name']."` ".strtoupper($type)." "
                .$increment." ".$tableKey.PHP_EOL.$isNull.PHP_EOL;
            $schema .= $comma;

            $i++;
        }

        $schema.= ') ENGINE='.$engine.' DEFAULT  CHARSET='.$charset.';'.PHP_EOL;

        $this->schema = $schema;

        return $this;
    }

    public function drop($table = '')
    {
        $tableName = '';

        $tableName = ($table !=='') ?
            $table : $this->tableName;

        $this->schema = strtoupper(__FUNCTION__).' TABLE IF EXISTS
        `'.$this->database.'`.`'.$tableName.'`'.PHP_EOL;

        return $this;
    }

    /**
     * Rename the database table.
     *
     * @param  array|string $tableNames
     * @return this pointer
     *
     */
    public function rename($tableNames = array())
    {
        $schema = '';

        $schema .= strtoupper(__FUNCTION__).' TABLE '.PHP_EOL;

        if (is_array($tableNames)) {

            $i=0;

            $arrCount = count($tableNames);

            foreach ($tableNames as $key => $value) {

                $schema .= '`'.$key.'` TO `'.$value.'`';

                $comma =  ($i < $arrCount-1) ? ',' : '';
                $schema .= $comma.PHP_EOL;
                $i++;
            }
        } else {
            $schema .= '`'.$this->database.'.`'.$this->tableName.'` TO `'.$tableNames.'`'.PHP_EOL;
        }

        $this->schema = $schema;

        return $this;
    }

    public function hasTable($table = '')
    {
        $tableName = '';

        $tableName = ($table !== '') ?
            $table : $this->tableName;

        $this->schema = "SHOW TABLES LIKE '".$tableName."'";

        return $this;
    }

    public function alter()
    {

    }

    public function __call($name, $arguments)
    {
        $callMethod = null;

        if (strpos($name, 'Column') == true) {

            $callMethod = explode('Column', $name);

            if (trim($callMethod[0]) == 'drop') {
                $arguments[1] = trim($callMethod[0]);
                $arguments[2] = trim($callMethod[0]);
            }

            if (trim($callMethod[0]) == 'add') {

                if (isset($arguments[0]) && is_array($arguments[0])) {
                    $arguments[1] = '';
                    $arguments[2] = trim($callMethod[0]);
                } else {
                    $arguments[2] = trim($callMethod[0]);
                }
            }

            if (!empty($arguments)) {
                if (method_exists($this, 'column')) {
                    return call_user_func_array(array($this, 'column'), $arguments);
                }
            }
        }

        throw new \BadMethodCallException("Invalid method $name called ");
    }

    /**
     *
     *
     *
     */
    public function column($columns, $definition = null, $type)
    {
        if (is_null($columns)) {
            throw new \BadMethodCallException("Column cannot be empty.");
        }

        if (is_array($columns)) {

            $column  = $columnKey = $columnValue= '';
            $i = 0;
            $arrCount = count($columns);

            foreach ($columns as $key => $value) {

                if (trim($type) == 'drop') {
                    $columnKey = '';
                    $columnValue = "`$value`";
                    $column .= strtoupper(trim($type)).' '.$columnKey.' '.$columnValue;
                }

                if (trim($type) == 'add') {
                    $columnKey = $key;
                    $columnValue = strtoupper($value);
                    $column .= strtoupper($type).' ('.$columnKey.' '.$columnValue.')';
                }

                $comma =  ($i < $arrCount-1) ? ',' : '';

                $column .= $comma;

                $i++;
            }

            $this->schema = self::ALTER_TABLE.'`'.$this->database.'`.`'.$this->tableName.'`
            '.$column.';';
        }

        if (is_string($columns)
            && $definition != null
        ) {
            /** @var $definition TYPE_NAME */
            $definition =(trim($type) == 'drop') ? '' : strtoupper($definition);

            $this->schema = self::ALTER_TABLE.'`'.$this->database.'`.`'.$this->tableName.'`
            '.strtoupper($type).' `'.$columns.'` '.$definition.';';
        }

        return $this;

    }

    public function modifyColumn()
    {

    }

    public function after($column)
    {

        $this->schema = str_replace(';', ' ', $this->schema).''.strtoupper(__FUNCTION__).' '.$column.';';

        return $this;

    }

    private function getSchemaQuery()
    {
        return "".$this->_informationSchema.".COLUMNS
                        WHERE TABLE_SCHEMA = '".$this->database."'
                        AND TABLE_NAME = '".$this->tableName."'";
    }

    public function getColumns()
    {
        $this->schema = self::SELECT." COLUMN_NAME FROM ".$this->getSchemaQuery();

        return $this;
    }

    /**
     *
     */
    public function hasColumn($column)
    {
        $this->schema = self::SELECT." COUNT(COLUMN_NAME) FROM
                        ".$this->getSchemaQuery()."
                        AND COLUMN_NAME = '".$column."' ";

        return $this;

    }
    // string for single column and array for multiple column
    public function addPrimaryKey($columns)
    {
        $schema = self::SELECT." EXISTS
                   (
                       ".self::SELECT." * FROM ".$this->_informationSchema.".columns
                       WHERE ".$this->_tableSchema."= '".$this->database."' AND
                       table_name ='".$this->tableName."' AND
                       column_key = 'PRI'

                   ) AS has_primary_key;";

        $hasPrimaryKey = $this->_connection->prepare($schema)->execute();

        if ($hasPrimaryKey === true) {
            $query = '';
            $query = self::ALTER_TABLE."`".$this->tableName."` CHANGE
             `".$this->primaryKey."` `".$this->primaryKey."` INT( 11 ) NOT NULL";
            $primaryKey = $this->_connection->prepare($query)->execute();

            $schemaString = '';
            $schemaString = $this->commands($columns);

            $this->schema = static::ALTER_TABLE.' `'.$this->database.'`.`'.$this->tableName.'` DROP PRIMARY KEY,
                ADD CONSTRAINT PK_'.strtoupper($this->tableName).'_ID
                PRIMARY KEY ('.$schemaString.')';
        } else {
            $this->schema = static::ALTER_TABLE.' `'.$this->database.'`.`'.$this->tableName.'` ADD
            '.'PRIMARY KEY ('.$columns.')';
        }

        return $this;

    }

    public function dropPrimary()
    {
        $this->schema = static::ALTER_TABLE.'
        `'.$this->database.'`.`'.$this->tableName.'` DROP PRIMARY KEY';

    }

    private function commands($params)
    {

        if (is_array($params)) {

            $param  = '';
            $i = 0;
            $arrCount = count($params);

            foreach ($params as $key => $value) {

                $param .=  $value;

                $comma =  ($i < $arrCount-1) ? ',' : '';

                $param .= $comma;

                $i++;
            }

        }

        if (is_string($params)) {
            $param = $params;
        }

        return $param;
    }


    public function unique($column, $keyConstraint = '')
    {
        $alter = '';
        $alter = static::ALTER_TABLE.' `'.$this->tableName.'` ADD ';

        if (is_array($column)) {
            // build query unique key for multiple columns
            $columns = $this->commands($column);

            $this->schema = $alter.' CONSTRAINT
            UC_'.strtoupper($this->tableName).'_'.strtoupper($keyConstraint).'_ID
            '.strtoupper(__FUNCTION__).' ('.$columns.')';

        }

        if (is_string($column)) {
            $this->schema = $alter.' '.strtoupper(__FUNCTION__).' ('.$column.');';
        }

        return $this;
    }

    public function dropUnique($keyConstraint = '')
    {
        // ALTER TABLE Persons DROP CONSTRAINT uc_PersonID
        // MYSQL QUERY
        $this->schema = static::ALTER_TABLE.' `'.$this->tableName.'`
            DROP INDEX
            UC_'.strtoupper($this->tableName).'_'.strtoupper($keyConstraint).'_ID';

        return $this;

    }

    public function index($columnName, $indexName = '')
    {
        $indexName = ($indexName !== '') ? $indexName : $columnName;
        $this->schema = 'CREATE INDEX '.strtoupper($indexName).'_INDEX
        ON `'.$this->tableName.'` ('.$columnName.')';

        return $this;
    }

    public function dropIndex($indexName)
    {
        $this->schema = static::ALTER_TABLE.' `'.$this->tableName.'`
            DROP INDEX '.strtoupper($indexName).'_INDEX';

        return $this;
    }

    //Foreign key References to table
    public function addForeignKey()
    {

    }

    public function referenceTo()
    {

    }

    public function dropForeignKey()
    {


    }

    public function onDelete()
    {

    }

    public function onSave()
    {

    }

    /**
     * Build schema and return result set
     * @return bool
     */
    public function run()
    {
        if (is_object($this->_connection)) {
            $stmt = $this->_connection->prepare($this->schema);

            if ($return = $stmt->execute()) {
                return $return;
            } else {
                return false;
            }
        }
    }

    public function createDatabase($database)
    {
        $this->schema = 'CREATE DATABASE '.$database;

        return $this;
    }

    public function setTableSchema()
    {
        /** @var $this TYPE_NAME */
        $this->schema = self::SELECT.' '.strtoupper($this->_tableSchema).",TABLE_NAME,COLUMN_NAME,DATA_TYPE,
                        `COLUMN_KEY`,`Extra`
                        FROM ".strtoupper($this->_informationSchema).".COLUMNS
                        WHERE ".strtoupper($this->_tableSchema)." = '".$this->database."' AND
                        TABLE_NAME = '".$this->tableName."'";

        return $this;
    }

    public function setDbConnection($conn, $database ='')
    {
        $this->_connection = $conn;
        $this->database = $database;
    }
}
