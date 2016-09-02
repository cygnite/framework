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

use Closure;
use Cygnite\Database\ConnectionManagerTrait;
use Cygnite\Helpers\Inflector;

/*
 * Database Schema Builder
 *
 * Build and alter your database schema on the fly.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

class Schema
{
    use ConnectionManagerTrait;

    const ALTER_TABLE = 'ALTER TABLE ';
    const SELECT = 'SELECT';
    public $database;
    public $primaryKey;
    public $tableName;
    public $schema = [];
    protected $_connection;
    private $klass;
    private $_informationSchema = 'INFORMATION_SCHEMA';
    private $_tableSchema = 'TABLE_SCHEMA';

    protected $checkSchemaExistence = false;

    /**
     * You cannot create an instance of Schema class
     * directly.
     *
     * @param $model
     * @param null $database
     *
     * @throws \ErrorException
     */
    protected function __construct($model = null, $database = null)
    {
        if (!is_null($model) && is_object($model)) {
            $this->klass = $model;

            if (!class_exists(get_class($this->klass))) {
                throw new \ErrorException(sprintf("Class %s doesn't exists", get_class($this->klass)));
            }

            $reflectionClass = new \ReflectionClass(get_class($this->klass));
            /*
             | We will set the database connection name here
             */
            if (property_exists($this->klass, 'database')) {
                $reflectionProperty = $reflectionClass->getProperty('database');
                $reflectionProperty->setAccessible(true);
                $database = $reflectionProperty->getValue($this->klass);
            }

            // If table name not provided we will format and consider class name as table
            if (!property_exists($this->klass, 'tableName')) {
                $model = Inflector::tabilize(get_class($this->klass));
            }
        }

        $this->setDatabase((!is_null($database)) ? $database : $this->getDefaultConnection());
        $this->setTableName($model);
    }

    /**
     * Set the database connection.
     *
     * @param $database
     */
    public function setDatabaseConnection($database)
    {
        $this->_connection = $this->getConnection($database);
    }

    /**
     * @param       $method
     * @param array $arguments
     *
     * @throws \Exception
     *
     * @return callable|void
     */
    public static function __callStatic($method, $arguments = [])
    {
        throw new \Exception(sprintf(
            'Oops, Undefined method called %s', 'Schema::'.$method
        ));
    }

    /**
     * Set the connection object.
     *
     * @param $connection
     *
     * @return $this
     */
    public function on($connection)
    {
        $this->setDatabase($connection);
        $this->_connection = null;

        $this->setDatabaseConnection($this->getDatabase());

        return $this;
    }

    /**
     * Set the database connection name.
     *
     * @param $database
     *
     * @return $this
     */
    private function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get the table name.
     *
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Set table name.
     *
     * @param $table
     *
     * @return $this
     */
    public function setTableName($table)
    {
        $this->tableName = $table;

        return $this;
    }

    /**
     * Create database schema and return the schema instance.
     *
     * @param $table
     * @param callable $closure
     *
     * @return mixed
     */
    public static function make($table, Closure $closure)
    {
        if (is_array($table)) {
            $table = isset($table['table']) ? $table['table'] : $table;
            $database = isset($table['database']) ? $table['database'] : null;
            $schema = new static($table, $database);
        } else {
            $schema = new static($table);
        }

        $callback = $closure($schema);
        /*
        | Set database connection name
        */
        $schema->on($schema->getDatabase());
        $schema->run();

        return $callback;
    }

    /**
     * Return the connection.
     *
     * @return null
     */
    public function connection()
    {
        return isset($this->_connection) ? $this->_connection : null;
    }

    /**
     * We will create table schema here.
     *
     * @param        $columns
     * @param string $engine
     * @param string $charset
     *
     * @return $this
     */
    public function create($columns, $engine = 'MyISAM', $charset = 'utf8')
    {
        $schema = $comma = $isNull = $tableKey = $type = '';

        $schema .= strtoupper(__FUNCTION__).' TABLE IF NOT EXISTS
        `'.$this->database.'`.`'.$this->tableName.'` (';
        $arrCount = count($columns);
        $i = 0;

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

            switch (strtolower($value['type'])) {

                case 'int':
                    $len = (isset($value['length'])) ? $value['length'] : 11;
                    list($type, $length) = $this->columnType($value['type'], $len);
                    break;
                case 'char':
                    list($type, $length) = $this->columnType($value['type'], $value['length'], 2);
                    break;
                case 'string':
                    list($type, $length) = $this->columnType('varchar', $value['length'], 200);
                    break;
                case 'text':
                    $type = 'TEXT';
                    break;
                case 'longtext':
                    $type = $value['type'];
                    break;
                case 'float':
                    list($type, $length) = $this->columnType($value['type'], $value['length'], '10,2');
                    break;
                case 'decimal':
                    list($type, $length) = $this->columnType($value['type'], $value['length'], '10,2');
                    break;
                case 'enum':
                    $length = implode('","', $value['length']);
                    $type = strtoupper($value['type']).'("'.$length.'")';
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $len = (isset($value['length'])) ? $value['length'] : "NOT NULL DEFAULT '0000:00:00 00:00:00'";
                    $type = strtoupper($value['type']).' '.$len;
                    $isNull = '';
                    break;
                case 'time':
                    $type = 'time';
                    break;
                case 'timestamp':
                    $len = (isset($value['length'])) ? $value['length'] : '';
                    $type = strtoupper($value['type']).' '.$len;
                    break;
            }

            $comma = ($i < $arrCount - 1) ? ',' : '';

            $schema .= '`'.$value['column'].'` '.strtoupper($type).' '
                .$increment.' '.$tableKey.PHP_EOL.$isNull.PHP_EOL;
            $schema .= $comma;

            $i++;
        }

        $schema .= ') ENGINE='.$engine.' DEFAULT  CHARSET='.$charset.';'.PHP_EOL;

        $this->schema = $schema;

        return $this;
    }

    /**
     * @param      $type
     * @param      $length
     * @param null $default
     *
     * @return array
     */
    public function columnType($type, $length, $default = null)
    {
        $length = (isset($length) && $length !== '') ? $length : $default;
        $type = strtoupper($type).'('.$length.')';

        return [$type, $length];
    }

    /**
     * Drop table if exists.
     *
     * @param string $table
     *
     * @return $this
     */
    public function drop($table = '')
    {
        $tableName = '';

        $tableName = ($table !== '') ?
            $table : $this->tableName;

        $this->schema = strtoupper(__FUNCTION__).' TABLE IF EXISTS
        `'.$this->database.'`.`'.$tableName.'`'.PHP_EOL;

        return $this;
    }

    /**
     * Rename the database table.
     *
     * @param array|string $tableNames
     *
     * @return this pointer
     */
    public function rename($tableNames = [])
    {
        $schema = '';

        $schema .= strtoupper(__FUNCTION__).' TABLE '.PHP_EOL;

        if (is_array($tableNames)) {
            $i = 0;

            $arrCount = count($tableNames);

            foreach ($tableNames as $key => $value) {
                $schema .= '`'.$key.'` TO `'.$value.'`';

                $comma = ($i < $arrCount - 1) ? ',' : '';
                $schema .= $comma.PHP_EOL;
                $i++;
            }
        } else {
            $schema .= '`'.$this->database.'.`'.$this->tableName.'` TO `'.$tableNames.'`'.PHP_EOL;
        }

        $this->schema = $schema;

        return $this;
    }

    /**
     * Check table Existence.
     *
     * @param string $table
     *
     * @return $this
     */
    public function hasTable($table = '')
    {
        $tableName = '';

        $tableName = ($table !== '') ?
            $table : $this->tableName;

        $this->schema = "SHOW TABLES LIKE '".$tableName."'";
        $this->checkSchemaExistence = true;

        return $this;
    }

    public function alter()
    {
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
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
                    return call_user_func_array([$this, 'column'], $arguments);
                }
            }
        }

        throw new \BadMethodCallException(sprintf(
            'Oops, Undefined method called %s', 'Schema::'.$name
        ));
    }

    /**
     * @param      $columns
     * @param null $definition
     * @param      $type
     *
     * @throws \BadMethodCallException
     *
     * @return $this
     */
    public function column($columns, $definition, $type)
    {
        if (is_null($columns)) {
            throw new \BadMethodCallException('Column cannot be empty.');
        }

        if (is_array($columns)) {
            $column = $columnKey = $columnValue = '';
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

                $comma = ($i < $arrCount - 1) ? ',' : '';

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
            $definition = (trim($type) == 'drop') ? '' : strtoupper($definition);

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

    /**
     * get Columns of table schema.
     *
     * @return $this
     */
    public function getColumns()
    {
        $this->schema = self::SELECT.' COLUMN_NAME FROM '.$this->getSchemaQuery();

        return $this;
    }

    /**
     * @return string
     */
    private function getSchemaQuery()
    {
        return ''.$this->_informationSchema.".COLUMNS
                        WHERE TABLE_SCHEMA = '".$this->database."'
                        AND TABLE_NAME = '".$this->tableName."'";
    }

    /**
     * Check if column exists.
     *
     * @param $column
     *
     * @return $this
     */
    public function hasColumn($column)
    {
        $this->schema = self::SELECT.' COUNT(COLUMN_NAME) FROM
                        '.$this->getSchemaQuery()."
                        AND COLUMN_NAME = '".$column."' ";

        $this->checkSchemaExistence = true;

        return $this;
    }

    /**
     * String for single column and array for multiple column.
     *
     * @param $columns
     *
     * @return $this
     */
    public function addPrimaryKey($columns)
    {
        $schema = self::SELECT.' EXISTS
                   (
                       '.self::SELECT.' * FROM '.$this->_informationSchema.'.COLUMNS
                       WHERE '.$this->_tableSchema."= '".$this->database."' AND
                       TABLE_NAME ='".$this->tableName."' AND
                       COLUMN_KEY = 'PRI'

                   ) AS has_primary_key;";

        $hasPrimaryKey = $this->_connection->prepare($schema)->execute();

        if ($hasPrimaryKey === true) {
            $query = '';
            $query = self::ALTER_TABLE.'`'.$this->tableName.'` CHANGE
             `'.$this->primaryKey.'` `'.$this->primaryKey.'` INT( 11 ) NOT NULL';
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

    /**
     * @param $params
     *
     * @return string
     */
    private function commands($params)
    {
        if (is_array($params)) {
            $param = '';
            $i = 0;
            $arrCount = count($params);

            foreach ($params as $key => $value) {
                $param .= $value;

                $comma = ($i < $arrCount - 1) ? ',' : '';

                $param .= $comma;

                $i++;
            }
        }

        if (is_string($params)) {
            $param = $params;
        }

        return $param;
    }

    /**
     * Drop Primary key if exists.
     */
    public function dropPrimary()
    {
        $this->schema = static::ALTER_TABLE.'
        `'.$this->database.'`.`'.$this->tableName.'` DROP PRIMARY KEY';
    }

    /**
     * Create unique key index.
     *
     * @param        $column
     * @param string $keyConstraint
     *
     * @return $this
     */
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

    /**
     * Drop unique key index.
     *
     * @param string $keyConstraint
     *
     * @return $this
     */
    public function dropUnique($keyConstraint = '')
    {
        // ALTER TABLE Persons DROP CONSTRAINT uc_PersonID
        // MYSQL QUERY
        $this->schema = static::ALTER_TABLE.' `'.$this->tableName.'`
            DROP INDEX
            UC_'.strtoupper($this->tableName).'_'.strtoupper($keyConstraint).'_ID';

        return $this;
    }

    /**
     * Create index for the column.
     *
     * @param        $columnName
     * @param string $indexName
     *
     * @return $this
     */
    public function index($columnName, $indexName = '')
    {
        $indexName = ($indexName !== '') ? $indexName : $columnName;
        $this->schema = 'CREATE INDEX '.strtoupper($indexName).'_INDEX
        ON `'.$this->tableName.'` ('.$columnName.')';

        return $this;
    }

    /**
     * Drop index from the column.
     *
     * @param $indexName
     *
     * @return $this
     */
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

    public function createDatabase($database)
    {
        $this->schema = 'CREATE DATABASE '.$database;

        return $this;
    }

    /**
     * @param        $conn
     * @param string $database
     */
    public function setDbConnection($conn, $database = '')
    {
        $this->_connection = $conn;
        $this->database = $database;
    }

    public function setTableSchema()
    {
        /* @var $this TYPE_NAME */
        $this->schema = self::SELECT.' '.strtoupper($this->_tableSchema).',
                        TABLE_NAME,COLUMN_NAME,DATA_TYPE,
                        `COLUMN_KEY`,`Extra`,COLUMN_TYPE
                        FROM '.strtoupper($this->_informationSchema).'.COLUMNS
                        WHERE '.strtoupper($this->_tableSchema)." = '".$this->database."' AND
                        TABLE_NAME = '".$this->tableName."'";

        return $this;
    }

    /**
     * Get the schema prepared query.
     *
     * @return array
     */
    public function getSchemaPreparedQuery()
    {
        return $this->schema;
    }

    /**
     * Build schema and return result set.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function run()
    {
        if (is_object($this->connection())) {
            try {
                $stmt = $this->connection()->prepare($this->schema);

                if ($this->checkSchemaExistence) {
                    return $stmt->rowCount();
                } elseif ($return = $stmt->execute()) {
                    return $return;
                } else {
                    return false;
                }
            } catch (\PDOException $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
