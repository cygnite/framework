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
use Cygnite\Foundation\Collection;
use Cygnite\Database\Table\Schema;
use Cygnite\Database\Query\Builder as QueryBuilder;

abstract class ActiveRecord implements ActiveRecordInterface
{
    // Default foreign key suffix used by relationship methods
    const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';

    // Holds Activerecord instance
    public static $ar;

    // Default primary key
    public static $defaultPrimaryKey = 'id';

    // Valid Model events
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
    // Holds primary key value
    private $index;

    // Valid finders methods
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

    //set user defined table name into it.
    protected $modelClass;

    // Set Model attributes to store or update
    protected $attributes = [];

    // Holds Pagination Uri number
    protected $paginationUri;
    // Holds Pagination Offset
    protected $paginationOffset;

    // Holds page numbers
    protected $pageNumber;

    //Model class Name
    protected $modelClassNs;

    //Holds Query Builder Instance
    protected $query;

    //Holds database connection name into it.
    protected $database;

    // Holds Table name
    protected $tableName;

    //Holds primary key
    protected $primaryKey;
    protected $primaryKeyValue;

    // Holds model relations array
    protected $relations = [];

    /**
     * Set Model Attributes and start booting Cyrus ActiveRecord ORM
     *
     */
    public function __construct()
    {
        $model = null;
        static::$ar = $this;
        $model = get_class(static::$ar);
        $this->modelClassNs = $model;

        $this->setModelAttributes($model);
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

    /**
     * Return empty model object
     *
     * @return mixed
     */
    public function returnEmptyObject()
    {
        return self::model();
    }

    /**
     * Configure and set all attributes into model class
     *
     * @param $model
     * @throws \InvalidArgumentException
     */
    protected function setModelAttributes($model)
    {
        $this->setModelClass(Inflector::getClassNameFromNamespace($model));

        if (!property_exists($model, 'tableName') || is_null($this->tableName)) {
            $this->setTableName(Inflector::tabilize($this->getModelClass()));
        }

        if (!property_exists($model, 'database') || is_null($this->database)) {
            $this->setDatabase($this->query()->getDefaultConnection());
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
    protected function setPrimaryKey()
    {
        // making default primary key as id
        $primaryKey = isset($this->primaryKey) && is_null($this->primaryKey) ?
            static::$defaultPrimaryKey :
            $this->primaryKey;

        $this->primaryKey = $primaryKey;
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

    /**
     * Get primary Key Value
     *
     * @return mixed|null
     */
    public function getPrimaryKey()
    {
        return $this->primaryKeyValue;
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
     * Get the Id stored into object
     *
     * @param null $key
     * @return mixed
     */
    public function getId($key = null)
    {
        return (isset($this->index[$key]) && !is_null($key)) ?
            $this->index[$key] :
            $this->index[$this->getKeyName()];
    }

    /**
     * Get Id column Alias method of getId()
     *
     * @param $class
     * @return null|string
     */
    public function getIdColumn($class)
    {
        $column = $this->getTableNameFromClass($class, 'primaryKey');

        return (is_null($column) ? $this->getKeyName() : $column);
    }

    /**
     * Return Model Class with Namespace
     *
     * @return string
     */
    public function getModelClassNs()
    {
        return $this->modelClassNs;
    }

    /**
     * Returns table name from Model class properties
     *
     * @param        $class
     * @param string $property
     * @param null   $default
     * @return null
     */
    public function getTableNameFromClass($class, $property = 'tableName', $default = null)
    {
        if (!class_exists($class) || !property_exists($class, $property)) {
            return $default;
        }

        $properties = get_class_vars($class);

        return $properties[$property];
    }

    /**
     * Get Foreign key of table
     *
     * @param $table
     * @return string
     */
    protected static function getForeignKey($table)
    {
        return Inflector::singularize($table).self::DEFAULT_FOREIGN_KEY_SUFFIX;
    }

    /**
     * Find method to retrive data based on primary key id
     *
     * @param $argument
     * @return mixed
     */
    public static function find($argument)
    {
        return static::model()->findByPk($argument);
    }

    /**
     * It will return first row of the table
     *
     * @return mixed
     */
    public static function first()
    {
        return static::model()->query()->first();
    }

    /**
     * Return all collection of model data
     *
     * @param array $arguments
     * @return mixed
     */
    public static function all($arguments = [])
    {
        return static::model()->query()->all($arguments);
    }

    /**
     * We will return last row of the table
     *
     * @return mixed
     */
    public static function last()
    {
        return static::model()->query()->last();
    }

    /**
     * We will execute raw query into table given by user
     * and return resultset
     *
     * @param string $query
     * @return object Collection
     */
    public static function sql($query)
    {
        return static::$ar->query()->findBySql($query);
    }

    /**
     * Join model with another model and return query instance
     *
     * $model->joinWith(['person', 'constraint', 'alias']);
     *
     * @param $arguments
     * @return mixed
     */
    public function joinWith($arguments)
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

        return $this->query()->leftOuterJoin($tableWith, $params, $arguments[2]);
        }

    /**
     * Return Last Executed query
     *
     * @return mixed
     */
    public static function lastQuery()
    {
        return static::model()->query()->lastQuery();
        }

    /**
     * Create Pagination links and return
     *
     * @return $this|mixed
     */
    public static function createLinks()
    {
        $pagination = Pagination::make(static::model());
        $pagination->setPerPage();

        return $pagination->createLinks();
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
     * Set array of attributes directly into model object
     *
     * @param array $attributes
     * @return mixed|void
     * @throws DatabaseException
     */
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

    /**
     * Set attributes into model class
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Getter method
     *
     * @param $key
     * @return null
     * @throws \Exception
     */
    public function __get($key)
    {
        /*
         | We will check if the key exists into
         | relations array and return it
         */
        if (isset($this->relations[$key])) {
            return $this->relations[$key];
        }

        try {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
    }
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
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
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

    /**
     * Save model attributes into database
     *
     * @param array $attributes
     * @return mixed
     */
    public function save($attributes = [])
    {
        $attributes = (empty($attributes) || is_null($attributes)) ? $this->getAttributes() : $attributes;

        return $this->_save($attributes);
    }

    /**
     * Interally called to identify user tries to insert or
     * update the object
     *
     * @param $arguments
     * @return mixed
     */
    private function _save($arguments)
    {
        if ($this->isNew()) {
            // insert a new row
            return $this->setAttributesForInsertOrUpdate($arguments, 'insert');
        }

        //update the row using primary key
        return $this->setAttributesForInsertOrUpdate($arguments, 'update');
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

    /**
     * @param $arguments
     * @param $method
     * @return mixed
     */
    private function setAttributesForInsertOrUpdate($arguments, $method)
    {
        $query = $this->query();

        if ($method == 'insert') {
            return $query->{$method}($arguments);
        }

        return $query->where($this->getKeyName(), '=', $this->index[$this->getKeyName()])
                     ->{$method}($arguments);
    }

    /**
     * Intermediate method to call query builder trash method
     *
     * @param      $arguments
     * @param bool $multiple
     * @return mixed
     */
    public function trash($arguments, $multiple = false)
    {
        return $this->query()->{__FUNCTION__}($arguments, $multiple);
    }

    /**
     * Find Record by Primary Key Id
     *
     * @param $arguments
     * @return $this|mixed
     */
    public function findByPK($arguments)
    {
        $arguments = (array) $arguments;

        $args = [
            'primaryKey' => $this->getKeyName(),
            'args' => $arguments
        ];

        $fetch = $this->query()->find('find', $args);

        if ($fetch instanceof Collection && empty($fetch->asArray())) {
            return $this->returnEmptyObject();
        }

        $this->setId($this->getKeyName(), array_shift($arguments));
        $this->{$this->getKeyName()} = $fetch[0]->{$this->getKeyName()};

        foreach ($fetch[0]->getAttributes() as $key => $value) {
            $this->{$key} = $value;
        }

        $this->assignPropertiesToModel($this->attributes);

        return $this;
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

    /** ------------- Pagination functionalities ---------**/

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

    /**
     * Set Page Number for Pagination
     *
     * @param $number
     */
    public function setPageNumber($number)
    {
        $this->pageNumber = intval($number);
    }

    /**
     * Get the page number
     *
     * @return null
     */
    public function getPageNumber()
    {
        return (isset($this->pageNumber)) ? $this->pageNumber : null;
    }

    /**
     * Get Pagination Offset
     *
     * @return null
     */
    public function getPaginationOffset()
    {
        return (isset($this->paginationOffset)) ? $this->paginationOffset : null;
    }

    /**
     * Set Pagination Offset
     *
     * @param $offset
     */
    public function setPaginationOffset($offset)
    {
        $this->paginationOffset = intval($offset);
    }

    /**
     * We will get Fluent Query Object
     *
     * @return Query
     */
    public function query()
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
        static::$ar = new static();

        static::$ar->setDatabase($database);

        return static::$ar;
    }

    /**
     * Query on Table
     *
     * @param $table
     * @return QueryBuilder
     */
    public function table($table)
    {
        $this->setTableName($table);

        return $this->query();
    }

    /**
     * Get Database Connection
     *
     * @param $database
     * @return mixed
     */
    public static function connection($database)
    {
        static::$ar = new static();

        static::$ar->setDatabase($database);

        return static::$ar->query()->resolveConnection();
    }

    /**
     * This method is to build one-to-one releationship between
     * two table
     *
     * @param      $associatedClass
     * @param null $foreignKey
     * @param null $mappingKeyInBaseTable
     * @return Query\Builder
     */
    protected function hasOne($associatedClass, $foreignKey = null, $mappingKeyInBaseTable = null)
    {
        return $this->findHasOneOrMany($associatedClass, $foreignKey, $mappingKeyInBaseTable);
    }

    /**
     * This method use to construct one-to-many relationship of model class
     * We will build relations based on the foreign key mapped in associated table.
     *
     * @param      $associatedClass
     * @param null $foreignKey
     * @param null $mappingKeyInBaseTable
     * @return Query\Builder
     */
    protected function hasMany($associatedClass, $foreignKey = null, $mappingKeyInBaseTable = null)
    {
        return $this->findHasOneOrMany($associatedClass, $foreignKey, $mappingKeyInBaseTable);
    }

    /**
     * This method is use to construct one-to-one and one-to-many relationship
     * Make sure your base table has primary key as 'id' and mapped key table_name_id
     *
     * example: user : id , comment_id
     *          comment: id, commment
     *
     * @param      $associatedClass
     * @param null $foreignKey
     * @param null $mappingId
     * @return Query\Builder Object
     */
    protected function belongsTo($associatedClass, $foreignKey = null, $mappingId = null)
    {
        $associatedTable = $this->getTableNameFromClass($associatedClass);
        $foreignKey = $this->buildForeignKeyName($foreignKey, $associatedTable);
        $associatedTableId = $this->$foreignKey;

        if( is_null($mappingId) ) {
            return (new $associatedClass)->where($this->primaryKey, '=', $associatedTableId);
        }

        return (new $associatedClass)->where($mappingId, '=', $associatedTableId);
    }

    /**
     * This method is use to build queries for hasOne and hasMany methods.
     *
     * @param      $associatedClass
     * @param null $foreignKey
     * @param null $mappingId
     * @return Query\Builder Object
     */
    protected function findHasOneOrMany($associatedClass, $foreignKey = null, $mappingId = null)
    {
        $baseTable = $this->getTableName();
        $foreignKey = $this->buildForeignKeyName($foreignKey, $baseTable);

        $whereValue = '';
        $whereValue = $this->{$this->primaryKey};

        if(!is_null($mappingId)) {
            $whereValue = $this->{$mappingId};
        }

        /*
         | We will build query and return Query Builder object
         | to the user, to either make use of findAll() or findOne() method
         | to get data
         */
        return (new $associatedClass)->where($foreignKey, '=', $whereValue);
    }

    /**
     * This method is to build many to many relationships using model classes.
     *
     * @param      $associatedClass
     * @param null $joinModelClass
     * @param null $baseTableId
     * @param null $associatedTableId
     * @param null $firstKey
     * @param null $secondKey
     * @return Query\Builder Object
     *
     * @note Model Class must contain the property $tableName = 'table_name';
     */
    protected function hasManyThrough(
        $associatedClass,
        $joinModelClass = null,
        $baseTableId = null,
        $associatedTableId = null,
        $firstKey = null,
        $secondKey = null
    )
    {
        $baseClass = get_class($this);

        if (is_null($joinModelClass)) {
            $joinModelClass = $this->getJoinClassName($baseClass, $associatedClass);
        }

        // Get table names from each model class
        $classes = [$baseClass, $associatedClass, $joinModelClass];
        list($baseTable, $associatedTable, $joinTable) = $this->filterTableNameFromClass($classes);

        // Get baseTableId & associatedTableId from the given input
        $baseTableId = (is_null($firstKey)) ? $this->getIdColumn($baseClass) : $firstKey;
        $associatedTableId = (is_null($secondKey)) ? $this->getIdColumn($associatedClass) : $secondKey;

        // Get the mappingId and associatedId for joining table
        $mappingId = $this->buildForeignKeyName($baseTableId, $baseTable);
        $associatedTableId = $this->buildForeignKeyName($associatedTableId, $associatedTable);

        return (new $associatedClass)
            ->select("{$associatedTable}.*")
            ->innerJoin($joinTable, [
                    "{$associatedTable}.{$associatedTableId}",
                    '=',
                    "{$joinTable}.{$associatedTableId}"]
            )->where("{$joinTable}.{$mappingId}", '=', $this->$baseTableId);
    }

    /**
     * @param array $classes
     * @return array
     */
    protected function filterTableNameFromClass(array $classes)
    {
        $baseTable = $this->getTableNameFromClass($classes[0]);
        $associatedTable = $this->getTableNameFromClass($classes[1]);
        $joinTable = $this->getTableNameFromClass($classes[2]);

        return [$baseTable, $associatedTable, $joinTable];
    }

    /**
     * Get Join class name
     *
     * @param $baseClass
     * @param $associatedClass
     * @return string
     */
    private function getJoinClassName($baseClass, $associatedClass)
    {
        $classs = [Inflector::getClassName($baseClass), $associatedClass];
        sort($classs, SORT_STRING);

        return join("", $classs);
    }

    /**
     * @param $foreignKey
     * @param $baseTable
     * @return string
     */
    protected function buildForeignKeyName($foreignKey, $baseTable)
    {
        return (is_null($foreignKey)) ?
            Inflector::singularize($baseTable). self::DEFAULT_FOREIGN_KEY_SUFFIX :
            $foreignKey;
    }

    /**
     * Middleware method to allow user to dynamically change
     * query before executing and returning back.
     *
     * <code>
     * $book->filter('applyTax')->findMany();
     *
     * public function applyTax($query)
     * {
     *     return $query->where('tax', '=', '10%');
     * }
     * or
     *
     * $book->filter('applyTax', 'tax', '10%')->findAll();
     *
     * public function applyTax($query, $column, $value)
     * {
     *     return $query->where($column, '=', $value);
     * }
     *
     * </code>
     *
     * @return mixed
     */
    public function filter()
    {
        $args = func_get_args();
        $filterFunction = array_shift($args);
        array_unshift($args, $this);

        if (method_exists($this->modelClassNs, $filterFunction)) {
            return static::callDynamicMethod([$this->modelClassNs, $filterFunction], $args);
        }
    }

    /**
     * We will load associated model eagarly, solve n+1 query problem
     * Only two queries will get executed and build relation
     * collection object.
     *
     * @param $model
     * @return mixed
     */
    public static function with($model)
    {
        $data = static::model()->findAll();

        $idKey= null;
        $whereIn = [];
        foreach ($data as $key => $value) {
            $idKey = $value->primaryKey;
            $whereIn[] = $value->{$value->primaryKey};
        }

        $associatedModel = new $model;
        $associatedData = $associatedModel
            ->where(static::getForeignKey(static::model()->tableName), 'IN', implode(',', $whereIn))
            ->findAll();

        $data = static::buildRelations($data, $associatedModel, $associatedData);

        return $data;
    }

    /**
     * @param $data
     * @param $associatedModel
     * @param $associatedData
     * @return mixed
     */
    protected static function buildRelations($data, $associatedModel, $associatedData)
    {
        foreach($data as $parentKey => &$class) {

            $associateId = static::getForeignKey($class->tableName);
            $tempArray = [];
            $i = 0;
            foreach ($associatedData as $key => $value) {

                if ($value->{$associateId} == $class->{$class->primaryKey}) {
                    $tempArray[$i] = $value;
                    $i++;
                }
            }

            /*
             | Check cyrus activerecord has "relations" property and
             | append the cllection data into it
             */
            if (property_exists($class, 'relations')) {
                $class->relations[$associatedModel->getTableName()] = new Collection($tempArray);
            }
        }

        return $data;
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

                return self::model()->query()->find('findBy', $params);
                break;
            case 'joinWith' :
                return static::$ar->joinWith($class, $arguments);
                break;
        }

        //Use the power of PDO methods directly via static functions
        return static::callDynamicMethod(
            [self::model()->query()->resolveConnection(), $method],
            $arguments
        );
    }

    public static function callDynamicMethod($callback, $arguments = [])
    {
        return call_user_func_array($callback, $arguments);
    }

    public static function callFinderBy($method, $class, $arguments, $type = 'And')
    {
        if (string_has($method, $type)) {
            $query = static::$ar->query()->buildFindersWhereCondition($method, $arguments, $type);
            return $query->findAll();
        }
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
        // Find record by Id
        if ($method == 'find') {
            return $this->findByPk($method, $arguments);
        }

        // try calling method against Query if exists
        if (method_exists($this->query(), $method)) {
            return static::callDynamicMethod([$this->query(), $method], $arguments);
        }

        if (!method_exists($this->query()->resolveConnection(), $method) ||
            !method_exists($this->query(), $method)
        ) {
            throw new \BadMethodCallException("$method method not exists");
        }

        //|-----------------------------------------------
        //| If method not found we will check against the PDO.
        //| call PDO method directly via model object and return result set
        return call_user_func_array([$this->query()->resolveConnection(), $method], $arguments);
    }
}
