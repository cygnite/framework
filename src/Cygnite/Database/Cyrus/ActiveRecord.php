<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database\Cyrus;

/**
 * Database ActiveRecord.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Pagination;
use Cygnite\Database\Connection;
use Cygnite\Database\Table\Schema;
use Cygnite\Database\Query\Builder as QueryBuilder;

abstract class ActiveRecord implements ActiveRecordInterface
{
    const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';
    public static $ar;

    public static $defaultPrimaryKey = 'id';

    //set closed property as true is set else false
    private static $events = [
        'beforeCreate',
        'afterCreate',
        'beforeUpdate',
        'afterUpdate',
        'beforeSelect',
        'afterSelect',
        'beforeDelete',
        'afterDelete'
    ];

    //Hold all your table fields in attributes
    private static $validFinders = [
        'first',
        'last',
        'find',
        'findBy',
        'all',
        'findBySql',
        'findByAnd',
        'findByOr',
        'save'
    ];

    //set user defined database name into it.
    protected $primaryKeyValue;

    //set user defined table name into it.
    protected $modelClass;

    //set user defined table primary key
    //public $closed;
    protected $attributes = [];
    protected $paginationUri;
    protected $paginator = [];
    protected $paginationOffset;
    protected $pageNumber;
    protected $modelClassNs;
    protected $query;
    protected $database;
    protected $tableName;
    protected $primaryKey;

    // Default foreign key suffix used by relationship methods
    private $index;

    /*
     * Restrict users to create active records object Directly
     * Get the database configurations
     *
     */

    protected function __construct()
    {
        $model = null;
        static::$ar = $this;
        $model = get_class(static::$ar);
        $this->modelClassNs = $model;

        $this->setModelAttributes($model);
    }

    private function setModelAttributes($model)
    {
        $this->setModelClass(Inflector::getClassNameFromNamespace($model));

        if (!property_exists($model, 'tableName') || is_null($this->tableName)) {
            $this->setTableName(Inflector::tabilize($this->getModelClass()));
        }

        if (!property_exists($model, 'database') || is_null($this->database)) {
            $this->setDatabase(Connection::getDefaultConnection());
        } else {
            $this->setDatabase($this->database);
        }

        if (is_null($this->getDatabase())) {
            throw new \InvalidArgumentException(
                "Please specify database name in your model. " . get_called_class()
            );
        }

        $this->setPrimarykey();
    }

    /**
     * Set model class name
     *
     * @param $value
     */
    private function setModelClass($value)
    {
        $this->modelClass = $value;
    }

    /**
     * get model class name
     *
     * @return null
     */
    public function getModelClass()
    {
        return isset($this->modelClass) ? $this->modelClass : null;
    }

    /**
     * set the database name to connect
     *
     * @param $value
     */
    private function setDatabase($value)
    {
        $this->database = $value;
    }

    /**
     * get the database
     *
     * @return mixed|null
     */
    public function getDatabase()
    {
        return isset($this->database) ? $this->database : null;
    }

    /**
     * Set the primary key
     *
     */
    private function setPrimaryKey()
    {
        // making default primary key as id
        $primaryKey = isset($this->primaryKey) && is_null($this->primaryKey) ? 'id' : $this->primaryKey;

        $this->primaryKey = $primaryKey;
    }

    public static function find($argument)
    {
        return static::model()->findByPk($argument);
    }

    public static function first()
    {
        return static::model()->fluentQuery()->first();
    }

    public static function all($arguments = [])
    {
        return static::model()->fluentQuery()->find('all', $arguments);
    }

    public static function last()
    {
        return static::model()->fluentQuery()->last();
    }

    public static function findBySql($query)
    {
        return static::$ar->fluentQuery()->findBySql($query);
    }

    public static function createLinks()
    {
        $pagination = Pagination::make(static::model());

        return $pagination->createLinks();
    }

    public static function lastQuery()
    {
        return static::model()->fluentQuery()->lastQuery();
    }

    /**
     * The finder make use of __callStatic() to invoke
     * undefined static methods dynamically. This magic method is mainly used
     * for dynamic finders
     *
     * @param $method    String
     * @param $arguments array
     * @return object
     *
     */
    public static function __callStatic($method, $arguments)
    {
        $params = [];
        $class = self::model();

        switch ($method) {
            case (substr($method, 0, 6) == 'findBy') :

                if (strpos($method, 'And') !== false) {
                    return self::callFinderBy($method, $class, $arguments, 'And'); // findByAnd
                }

                if (strpos($method, 'Or') !== false) {
                    return self::callFinderBy($method, $class, $arguments, 'Or'); // findByOr
                }

                $columnName = Inflector::tabilize(substr($method, 6));
                $operator = (isset($arguments[1])) ? $arguments[1] : '=';
                $params = [$columnName, $operator, $arguments[0]];

                return self::model()->fluentQuery()->find('findBy', $params);
                break;
            case 'with' :
                return static::$ar->with($class, $arguments);
                break;
        }

        //Use the power of PDO methods directly via static functions
        return static::callDynamicMethod(
            [self::model()->fluentQuery()->getDatabaseConnection(), $method],
            $arguments
        );
    }

    /**
     * Get the model class instance
     *
     * @return mixed
     * @throws DatabaseException
     */
    public static function model()
    {
        $class = get_called_class();

        if ($class == __CLASS__) {
            throw new DatabaseException(sprintf("Abstract Class %s cannot be instantiated.", __CLASS__));
            }

        return (!class_exists($class)) ?: new $class();
    }

    public static function callDynamicMethod($callback, $arguments = [])
    {
        return call_user_func_array($callback, $arguments);
    }

    private static function callFinderBy($method, $class, $arguments, $type = 'And')
    {
        $params = [];

        if (strpos($method, $type) !== false) {
            $query = static::$ar->fluentQuery()->buildFindersWhereCondition($method, $arguments, $type);
            return $query->findAll();
        }
    }

    /*
    * Set your table columns dynamically
    * @access public
    * @param $key hold your table columns
    * @param $value hold your table column values
    * @return void
    *
    */
    public function getModelEvents()
    {
        return self::$events;
    }

    /**
     * get table name
     *
     * @return null
     */
    public function getTableName()
    {
        return isset($this->tableName) ? $this->tableName : null;
    }

    /**
     * Set the table name
     *
     * @param $value
     * @return $this
     */
    public function setTableName($value)
    {
        $this->tableName = $value;
        return $this;
    }


    public function setAttributes($attributes = [])
    {
        if (empty($attributes) || !is_array($attributes)) {
            throw new DatabaseException(sprintf("Invalid argument passed to %s", __FUNCTION__));
        }

        foreach ($attributes as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * Get attributes array
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return isset($this->attributes) ? $this->attributes : null;
    }

    public function __get($key)
    {
        try {
            return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param  mixed $offset <p>
     *                       The offset to retrieve.
     *                       </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->attributes[$offset] : null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link                  http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param  mixed $offset   <p>
     *                         An offset to check for.
     *                         </p>
     * @return boolean true on success or false on failure.
     *                        </p>
     *                        <p>
     *                        The return value will be casted to boolean if non-boolean was returned.
     */

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param  mixed $offset <p>
     *                       The offset to assign the value to.
     *                       </p>
     * @param  mixed $value  <p>
     *                       The value to set.
     *                       </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param  mixed $offset <p>
     *                       The offset to unset.
     *                       </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->__unset[$offset]);
        }
    }

    public function callFinder($arguments)
    {
        $class = $this;
        $method = $arguments['method'];
        return Query::_callMethod(
            function ($q) use ($method, $arguments) {
                return $q->find($method, $arguments);
            },
            $class
        );
    }

    public function findByAndOr()
    {
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function trash($arguments, $multiple = false)
    {
        return $this->fluentQuery()->{__FUNCTION__}($arguments, $multiple);
    }

    /**
     * Call framework defined method based on user input
     * We will call PDO methods using Model object
     *
     * $name method name
     * $arguments pass arguments to method dynamically
     * return mixed
     *
     */
    public function __call($method, $arguments = [])
    {
        // save attributes into table
        if (in_array($method, self::$validFinders) && $method == 'save') {
            return $this->_save($arguments);
        }

        // validate and call dynamic finders
        if (in_array($method, self::$validFinders) && $method == 'find') {
            return $this->findByPk($method, $arguments);
        }

        // try calling method against Query if exists
        if (method_exists($this->fluentQuery(), $method)) {
            return static::callDynamicMethod([$this->fluentQuery(), $method], $arguments);
        }

        if (!method_exists($this->fluentQuery()->getDatabaseConnection(), $method) ||
            !method_exists($this->fluentQuery(), $method)
        ) {
            throw new \BadMethodCallException("$method method not exists");
        }

        //|-----------------------------------------------
        //| If method not found we will check against the PDO.
        //| call PDO method directly via model object and return result set
        return call_user_func_array([$this->fluentQuery()->getDatabaseConnection(), $method], $arguments);
    }

    private function _save($arguments)
    {
        if (empty($arguments) && $this->isNew() == true) {
            // insert a new row
            return $this->setAttributesForInsertOrUpdate($arguments, 'insert');
        } else {
            //update the row using primary key
            return $this->setAttributesForInsertOrUpdate($arguments, 'update');
        }
    }

    /** Check id is null or not.
     *  If null return true else false
     *
     * @return bool
     */
    public function isNew()
    {
        return ($this->index[$this->primaryKey] == null) ? true : false;
    }

    private function setAttributesForInsertOrUpdate($arguments, $method)
    {
        if (method_exists($this->fluentQuery(), $method)) {
            if ($method == 'insert') {
                $arguments = $this->attributes;
            } else {
                $arguments[$this->getKeyName()] = $this->index[$this->getKeyName()];
            }
            return call_user_func_array([$this->fluentQuery(), $method], [$arguments]);
        }
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

    public function findByPK($arguments)
    {
        $arguments = (array) $arguments;

        $args = [
            'primaryKey' => $this->getKeyName(),
            'args' => $arguments
        ];

        $fetch = $this->fluentQuery()->find('find', $args);
        $this->setId($this->getKeyName(), array_shift($arguments));

        if ($fetch == null) {
            return $this->returnEmptyObject();
        }

        $this->{$this->getKeyName()} = $fetch[0]->{$this->getKeyName()};

        foreach ($fetch[0]->attributes as $key => $value) {
            $this->{$key} = $value;
        }

        $this->assignPropertiesToModel($this->attributes);

        return $this;
    }

    /**
     * Set the primary key id value
     *
     * @param $key
     * @param $value
     */
    private function setId($key, $value)
    {
        $this->index[$key] = $value;
    }

    /**
     * Return empty model object
     *
     * @return mixed
     */
    public function returnEmptyObject()
    {
        $class = self::model();
        $this->index[$this->primaryKey] = null;

        return new $class();
    }

    /**
     * We will assign values to model properties
     *
     * @param array $attributes
     */
    protected function assignPropertiesToModel($attributes = [])
    {
        $model = null;
        $model = self::model();
        foreach ($attributes as $key => $value) {
            $model->{$key} = $value;
        }
    }

    public function getPageNumber()
    {
        return (isset($this->pageNumber)) ? $this->pageNumber : null;
        $this->pageNumber = intval($number);
    }

    public function setPageNumber($number)
    {
        $this->pageNumber = intval($number);
    }

    public function getPaginationOffset()
    {
        return (isset($this->paginationOffset)) ? $this->paginationOffset : null;
    }

    public function setPaginationOffset($offset)
    {
        $this->paginationOffset = intval($offset);
    }

    public function getId($key)
    {
        return ($this->index[$key] !== null) ? $this->index[$key] : null;
    }

    /**
     * Set the pagination limit
     *
     * @param null $number
     */
    public function setPageLimit($number = null)
    {
        if (is_null($number)) {
            $number = $this->setPageLimit();
        }

        $pagination = Pagination::make();
        $pagination->setPerPage($number);
    }

    public function with($arguments)
    {
        $class = static::model();

        $tableWith = Inflector::tabilize($arguments[0]);

        $params = [
            $class->tableName . '.' . $class->primaryKey,
            '=',
            $tableWith . '.' . Inflector::singularize($class->tableName) . self::DEFAULT_FOREIGN_KEY_SUFFIX
        ];

        if (isset($arguments[1])) {
            $params = $arguments[1];
        }

        return $this->fluentQuery()->leftOuterJoin($tableWith, $params, $arguments[2]);
    }

    /**
     * We will get Fluent Query Object
     * @return Query
     */
    public function fluentQuery()
    {
        return new QueryBuilder($this);
    }

    /**
     * Use Connection to build fluent queries against any table
     *
     * @param $database
     * @return mixed
     */
    public static function db($database)
    {
        static::$ar->setDatabase($database);

        return static::$ar->fluentQuery();
    }

    /**
     * Get Database Connection
     *
     * @param $database
     * @return mixed
     */
    public static function connection($database)
    {
        static::$ar->setDatabase($database);
        return static::$ar->fluentQuery()->getDatabaseConnection();
    }

    public function getModelClassNs()
    {
        return $this->modelClassNs;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKeyValue;
    }
}
