<?php
namespace Cygnite\Database;

use PDO;
use Cygnite;
use Exception;
use PDOException;
use Cygnite\Inflector;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Cygnite\Base\Event;
use Cygnite\Database\Schema;
use Cygnite\Database\Connections;
use Cygnite\Libraries\Pagination;
use Cygnite\Database\Configurations;
use Cygnite\Database\Exceptions\DatabaseException;


/**
 *   Cygnite Framework
 *
 *   An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                   :  Packages
 * @Sub Packages              :  Database
 * @Filename                  :  ActiveRecord
 * @Description               :  Active Record to handle database manipulations.
 *                               As read,write,erase,update etc.
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @FileSource
 *
 */
 class ActiveRecord extends Connections
 {
    public $id;
    //Hold your connection object
    public $pdo;

    public $modelClass;

    //set closed property as true is set else false
    public $closed;

    //Hold all your table fields in attributes
    protected $attributes = array();

    public $data = array();

    //set your pdo statement here
    private $_statement;

    //hold all your fields name which to select from table
    private $_selectColumns;

    private $_fromWhere;

    private $_columnWhere;

    private $_whereType;

    private $_limitValue;

    private $_offsetValue;

    private $_columnName;

    private $_orderType;

    private $_groupBy;

    //set user defined database name into it.
    protected $database;

    //set user defined table name into it.
    protected $tableName;

    //set user defined table primary key
    protected $primaryKey;

    private $index;

    // set query builder query into property
    private $sqlQuery;

    private $debugQuery;

    private $distinct;

    protected $events = array(
        'beforeCreate',
        'afterCreate',
        'beforeUpdate',
        'afterUpdate',
        'beforeSelect',
        'afterSelect',
        'beforeDelete',
        'afterDelete'
    );

    const DELETE = 'DELETE';

    public $paginationUri;

    public $paginator = array();

    public $paginationOffset;

    public $pageNumber;

    private $validFinders = array(
        'first',
        'last',
        'find',
        'findBy',
        'all',
        'findBySql',
        'findByAnd',
        'findByOr',
        'save'
    );

     /*
      * Restrict users to create active records object Directly
      * Get the database configurations
      *
      */
    protected function __construct()
    {
        $model = null;
        $model = get_class($this);

        if (!empty($this->events)) {
            foreach ($this->events as $eventKey => $event) {
                Event::instance()->attach($event, '\\'.$model.'@'.$event);
            }
        }

        $this->modelClass = Inflector::instance()->getClassNameFromNamespace($model);

        if (!property_exists($model, 'tableName') || is_null($this->tableName)) {
            $this->tableName = Inflector::instance()->fromCamelCase($this->modelClass);
        }

        if (!property_exists($model, 'database')) {
            $this->database = $this->getDefaultConnection();
        }

        if (is_null($this->database)) {
            throw new \InvalidArgumentException(
                "Please specify database name in your model.".get_called_class()
            );
        }

        //$this->pdo = Connections::getConnection($this->database);
        $this->setDatabaseConnection($this->getConnection($this->database));
    }

    public function setAttributes($attributes = array())
    {
         foreach ($attributes as $key => $value) {
            $this->__set($key, $value);
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
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    /*
     * Get your table columns dynamically
     * @access public
     * @param $key
     * @return void
     *
     */
    public function __get($key)
    {
        try {
            return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * The finder make use of __callStatic() to invoke
     * undefinded static methods dynamically. This magic method is mainly used
     * for dynamic finders
     *
     * @param $method String
     * @param $arguments array
     * @return object
     *
     */
    public static function __callStatic($method, $arguments)
    {
        $class  = $params = null;
        $class = self::getDynamicInstance();

        if (substr($method, 0, 6) == 'findBy') {

            if ($method == 'findBySql') {
                return static::callDynamicMethod(array($class, $method), $arguments);
            }
            $params = array();

            if (strpos($method,'And') !== false) {

                $params = static::buildFindersWhereConditions($method, $arguments);
                return static::callDynamicMethod(array($class, 'findByAnd'), array($params));
            }

            if (strpos($method,'Or') !== false) {

                $params = static::buildFindersWhereConditions($method, $arguments, 'Or');
                return static::callDynamicMethod(array($class, 'findByOr'), array($params));
            }

            $columnName =  Inflector::instance()->fromCamelCase(substr($method, 6));
            $params = array();
            $condition = (isset($arguments[1])) ? $arguments[1] : '=';

            $params = array(
                    $columnName,
                    $condition,
                    $arguments[0],
              );

            return call_user_func_array(array($class, substr($method, 0, 6)), $params);
        }

        if ($method == 'first') {
            return static::callDynamicMethod(array($class, $method), $arguments);
        }

        if ($method == 'find') {

            return static::callDynamicMethod(array($class, $method), $arguments);
        }

        if ($method == 'all') {
            return static::callDynamicMethod(array($class, $method), $arguments);
        }

        if ($method == 'last') {
            return static::callDynamicMethod(array($class, $method), $arguments);
        }

        if ($method == 'createLinks') {
            $model = get_called_class();
            $pagination = null;
            $pagination = Pagination::instance(new $model());
            return $pagination->{$method}();
        }

        //Use the power of PDO methods directly via static functions
        return call_user_func_array(array(new $class, $method), $arguments);
    }

    /**
     * This method is mainly used for building where conditions as array
     * for dynamic finders.
     *
     *
     * @param $method String
     * @param $arguments array
     * @param $type string
     * @throws \Exception
     * @return object
     *
     */
    public static function buildFindersWhereConditions($method, $arguments, $type = 'And')
    {
        $conditions = array();
        $conditions = explode($type, str_replace('findBy', '', $method));

        if (count($conditions) == count($arguments[0])) {

            foreach ($conditions as $key => $value) {
                $field = Inflector::instance()->fromCamelCase($value);
                $params[$field.' ='] = isset($arguments[0][$key]) ?
                    trim($arguments[0][$key]) :
                    '';
            }
        } else {
            throw new Exception("Arguments doesn't matched with number of fields");
        }

        return $params;
    }

    public static function callDynamicMethod($callback, $arguments = array())
    {
        return call_user_func_array($callback, $arguments);
    }

    private static function getDynamicInstance()
    {
        $class = $child = $reflector = null;
        $class = get_called_class();
        if (class_exists($class)) {
            $reflector = new ReflectionClass($class);
            $child = $reflector->getProperty('primaryKey');

            return new $child->class;
        }
    }

    public function buildDynamicQuery()
    {

    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Call framework defined method based on user input
     * $name method name
     * $arguments pass arguments to method dynamically
     * return mixed
     *
     */
    public function __call($method, $arguments = array())
    {
        if (in_array($method, $this->validFinders) && $method == 'save') {

            if (empty($arguments) && $this->isNew() == true) {

                if (method_exists($this, 'insertInto')) {
                    return call_user_func_array(array($this, 'insertInto'), $arguments);
                }
            } else {

                if (method_exists($this, 'update')) {
                    if (empty($arguments)) {
                        $arguments[] = $this->index[$this->primaryKey];
                    }

                    return call_user_func_array(array($this,'update'), $arguments);
                }
            }
        }

        if (in_array($method, $this->validFinders) && $method == 'first') {

            $fetchObject = $this->select('*')
                ->orderBy($this->primaryKey)
                ->limit(1)
                ->findAll();

            if ($fetchObject == null) {
                return $this->returnEmptyObject();
            }

            return $fetchObject;
        }

        if (in_array($method, $this->validFinders) && $method == 'findByAnd') {

            return $this->select('*')->where($arguments[0])->findAll();
        }

        if (in_array($method, $this->validFinders) && $method == 'findByOr') {

            return $this->select('*')->where($arguments[0], '', 'OR')->findAll();
        }

        if (in_array($method, $this->validFinders) && $method == 'all') {

            if (isset($arguments[0]['orderBy'])) {
                $exp = array();
                $exp = explode(' ', $arguments[0]['orderBy']);
                $this->_columnName = (isset($exp[0])) ? $exp[0] : '';
                $this->_orderType = (isset($exp[1])) ? $exp[1] : '';
            } else {
                $this->_columnName = 'id';
            }

            if (isset($arguments[0]['paginate']) || method_exists($this, 'pageLimit')) {

                $page = $offset = $start = "";
                $offset = $this->perPage; //how many items to show per page
                $limit = !isset($arguments[0]['paginate']['limit']) ?
                                $this->pageLimit() :
                                $arguments[0]['paginate']['limit'];

                $page =  ($limit !== '')
                            ? $limit
                            : 0;

                if ($page) {
                    $start = ($page - 1) * $offset;//first item to display on this page
                } else {
                    $start = 0; //if no page var is given, set start to 0
                }

                $this->_limitValue = intval($start);
                $this->_offsetValue = intval($offset);
            }

            return $this->select('*')->findAll();
        }

        if (in_array($method, $this->validFinders) && $method == 'find') {

            $id = array_shift($arguments);
            $fetch = $this->select('*')->where($this->primaryKey, $id)
                ->orderBy($this->primaryKey,'DESC')
                          ->findAll();

            $this->setId($this->primaryKey, $id);

            if ($fetch == null) {
                return $this->returnEmptyObject();
            }

            $this->{$this->primaryKey} = $fetch[0]->{$this->primaryKey};
            foreach ($fetch[0]->attributes as $key => $value) {
                $this->{$key} = $value;
            }

            $this->assignPropertiesToModel($this->attributes);
            return $this;
        }

        if (in_array($method, $this->validFinders) && $method == 'findBy') {
            $columnName = "";
            $params = array();

            $params = array(
                     $arguments[0].' '.$arguments[1] => $arguments[2]
                 );

            $fetch = $this->select('*')->where($params)->findAll();

            if ($fetch == null) {
                return $this->returnEmptyObject();
            }

            return $fetch;
        }

        if (in_array($method, $this->validFinders) && $method == 'last') {

            $fetchObject = $this->select('*')
                ->orderBy($this->primaryKey, 'DESC')
                ->limit(1)
                ->findAll();

            if ($fetchObject == null) {
                return $this->returnEmptyObject();
            }

            return $fetchObject;
        }

        /** @var $method TYPE_NAME */
        if (in_array($method, $this->validFinders) && $method == 'findBySql') {
            $results = array();
            $fetchObject = $this->getDatabaseConnection()->prepare(trim($arguments[0]));
            $fetchObject->execute();
            $results = $this->fetchAs($fetchObject);
            return $results;
        }

        if  (!method_exists($this->getDatabaseConnection(), $method)) {
            throw new Exception("$method method not exists ");
        }
        return call_user_func_array(array($this->getDatabaseConnection(), $method), $arguments);

        //throw new \Exception("Invalid method $name called  ");
    }

    private function returnEmptyObject()
    {
        $class = self::getDynamicInstance();
        $this->index[$this->primaryKey] = null;
        return new $class;
    }

    public function lastQuery()
    {
        return $this->debugQuery;
    }

     public function setPageNumber($number)
     {
         $this->pageNumber = intval($number);
     }

     public function getPageNumber()
     {
         return (isset($this->pageNumber)) ? $this->pageNumber : null;
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

    private function extractConditionsReverse($arr)
    {
        //$pattern  = '/([A-Za-z_]+[A-Za-z_0-9]?)[ ]?(!=|=|<=|<|>=|>|like|clike|slike|not
        //              |is|in|between|and|or|IN|NOT|BETWEEN|LIKE|AND|OR)/';
        $pattern  = '/([\w]+)?[\s]?([\!\<\>]?\=|[\<\>]|[cs]{0,1}like|not
                    |i[sn]|between|and|or)?/i';

        $result = array();
        foreach ($arr as $key => $value) {

            preg_match($pattern, $key, $matches);
            $matches[1] = !empty($matches[1]) ? $matches[1] : null;
            $matches[2] = !empty($matches[2]) ? $matches[2] : null;

            $result []= array($matches[1], $matches[2], $value);

        }

        return $result;
    }

    private function setId($key, $value)
    {
        $this->index[$key] = $value;
    }

    public function getId($key)
    {
        return ($this->index[$key] !== null) ? $this->index[$key] : null;
    }

    protected function assignPropertiesToModel($attributes = array())
    {
        $model = null;
        $model = self::getDynamicInstance();
        foreach ($attributes as $key => $value) {

            $model->{$key} = $value;
        }
    }

    public function isNew()
    {
        return ($this->index[$this->primaryKey] == null) ? true : false;
    }

    private function findByColumn($key, $values = array())
    {

        //echo $key;
    }
    /*
     * Save data into table
     * @access private
     * @param $arguments empty array
     * @return true
     *
     */
    private function insertInto($arguments = array())
    {
        $fields = $values = array();
        $query = $debugQuery = "";
        if (method_exists($this, 'beforeCreate')) {
            Event::instance()->trigger('beforeCreate', $this);
        }
        $insertMethod = null;
        $insertMethod = str_replace('_', ' ',
            strtoupper(Inflector::instance()->fromCamelCase(__FUNCTION__))
        );

        foreach (array_keys($this->attributes) as $key) {

             $fields[] = "`$key`";
             $values[] = "'" .$this->attributes[$key] . "'";
             $placeholder[] = substr(str_repeat('?,', count($key)), 0, -1);
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);
        $placeHolders = implode(',', $placeholder);

        $query = $insertMethod." `".$this->database."`.`".$this->tableName."`
           ($fields) VALUES"." ($placeHolders)".";";

        $debugQuery = $insertMethod." `".$this->database."`.`".$this->tableName."`
           ($fields) VALUES"." ($values)".";";

        try {
            //$this->getDatabaseConnection()->quote($string, $parameter_type=null); have to write a method to escape strings
            $statement = $this->getDatabaseConnection()->prepare($query);

            if ( true == $statement->execute(array_values($this->attributes)))  {
                $this->{$this->primaryKey} = (int) $this->getDatabaseConnection()->lastInsertId();

                if (method_exists($this, 'afterCreate')) {
                    Event::instance()->trigger('afterCreate', $this);
                }
                return true;
            }
        } catch (PDOException  $exception) {
             throw new \RuntimeException($exception->getMessage()); //echo  $exception->getMessage();
        }
    }

    /*
    * Update user data into table by key
    * @access private
    * @param $args update by table fields
    * @return boolean
    *
    */
    private function update($args)
    {
        $query  =$debugQuery= $x = "";
        $updateBy = $updateValue = null;

        $updateMethod = null;
        $updateMethod = strtoupper(Inflector::instance()->fromCamelCase(__FUNCTION__));

        if (method_exists($this, 'beforeUpdate')) {
            Event::instance()->trigger('beforeUpdate', $this);
        }

        if ((is_array($args) && !empty($args) )) {
            $x = array_keys($args);
            $updateBy = $x[0];
            $updateValue = $args[$x[0]];
        } else {
            $updateBy = $this->primaryKey;
            $updateValue = $args;
        }


        $query .= $updateMethod." `".$this->database."`.`".$this->tableName."` SET ";
        $debugQuery .= $updateMethod." `".$this->database."`.`".$this->tableName."` SET ";
        $arrCount = count($this->attributes);
        $i = 0;

        foreach ($this->attributes as $key => $value) {

            $query .= " `".$key."` "."="." '".$value."'"." ";
            $debugQuery .= " `".$key."` "."="." '".$value."'"." ";
            $query .=  ($i < $arrCount-1) ? ',' : '';
            $debugQuery .=  ($i < $arrCount-1) ? ',' : '';

            $i++;
        }

            $query .=" WHERE ".$updateBy." =  :column";
            $debugQuery .=" WHERE ".$updateBy." = ".$updateValue;

            //$this->debugLastQuery($debugQuery);
        try {
            $statement = $this->getDatabaseConnection()->prepare($query);
            $statement->bindValue(':column', $updateValue);
            $statement->execute();

            if (method_exists($this, 'afterUpdate')) {
                Event::instance()->trigger('afterUpdate', $this);
            }

            return $statement->rowCount();

        } catch (\PDOException  $exception) {
             throw new Exception($exception->getMessage());
        }
    }

    /**
     * Trash method
     *
     * Delete rows from the table and runs the query
     *
     * @access    public
     * @param array $where
     * $multiple false
     * @param bool  $multiple
     * @throws \Exception
     * @internal  param \Cygnite\Database\the $string table to retrieve the results from
     * @return object
     */
    public function trash($where, $multiple = false)
    {
        $whr = array();
        $column = $value = $statement = null;

        if (method_exists($this, 'beforeDelete')) {
            Event::instance()->trigger('beforeDelete', $this);
        }

        if (is_array($where) && $multiple == false) {
            $whr = array_keys($where);
            $column = $whr[0];
            $value = $where[$whr[0]];

            $condition = "` WHERE `".$column."` = ".$value;
        }

        if (is_string($where) || is_int($where)) {

            $column = $this->primaryKey;
            $value = $where;
            $condition = "` WHERE `".$column."` = ".$value;
        }


        $values = array();

        if (is_array($where) && $multiple == true) {
            $condition = "` WHERE `id` IN (".implode(',', $where).")";
            $output = array_map(
                function ($val) {
                    return '?';
                },
                $where
            );
            $debugQuery = "` WHERE `id` IN (".implode(',', $output).")";
            $sqlQuery = self::DELETE." FROM `".$this->tableName.$condition;
            $debugQuery = self::DELETE." FROM `".$this->tableName.$debugQuery;
        } else {
            $sqlQuery =
            self::DELETE." FROM `".$this->tableName."` WHERE `".$column."` = :where";
            $debugQuery =
            self::DELETE." FROM `".$this->tableName."` WHERE `".$column."` = ".$value;

        }


        /** @var $exception TYPE_NAME */
        try {
            $statement = $this->getDatabaseConnection()->prepare($sqlQuery);

            if (is_array($values) && empty($values)) {
                $statement->bindValue(':where', $value);
            }

            $affectedRows = null;

            $affectedRows = $statement->execute();

            if (method_exists($this, 'afterDelete')) {
                Event::instance()->trigger('afterDelete', $this);
            }

            return $affectedRows;

        } catch (\PDOException  $exception) {
            throw new \Exception($exception->getMessage());
        }

    }

     /**
      * Find Function to selecting Table columns
      *
      * Generates the SELECT portion of the query
      *
      * @access    public
      * @param $type
      * @throws \Exception
      * @internal  param $string
      * @return    object
      */
    public function select($type)
    {
        //create where condition with and if value is passed as array
        if (is_string($type) && !is_null($type)) {
            if ($type === 'all' || $type == '*') {
                $this->_selectColumns = $this->tableName.'.*';
            } else {
                $this->_selectColumns = (string) $type; // Need to split the column name and add quotes
            }
        } else {
            throw new Exception("Accepted parameters should be string format.");
        }

        return $this;
    }

    /* Where
    *
    * Generates the WHERE portion of the query. Separates
    * multiple calls with AND
    * You can also use this method for WHERE IN(),
    * OR WHERE etc.
    * Example:
    * <code>
    * $this->where('field_name','value','=');
    *
    * $conditions = array(
    *                     'field_name1 LIKE' => '%Sanjoy%',
    * );                  'field_name2 LIKE' => 'Dey%',
    *
    * $this->where($conditions);
    *
    * $conditions2 = array(
    *                     'field_name1 LIKE' => '%Sanjoy%',
    * );                  'field_name2 =' => 'Dey',
    *
    * $this->where($conditions2,'OR');
    *
    *
    * $conditions3 = array(
    *                     'field_name1 IN' => '#"Automatic","Automated","Autoclaves"'
    * );
    *
    * $this->where($conditions2,'OR');
    *
    * $conditions4 = array(
    *                    'created_at BETWEEN' => '2012-12-27',
                          date('Y-m-d'),
    * );
    *
    * $this->where($conditions4);
    * </code>
    *
    * @access	public
    * @param	column name
    * @param	value
    * @return	object
    */
    public function where($columnName, $where = "", $type = null)
    {
        $resultArray = array();
        // Check whether value passed as array or not
        if (is_array($columnName)) {

            $arrayCount = count($columnName);
            $resultArray = $this->extractConditions($columnName);
            $arrayCount = count($resultArray);
            $i = 0;
            $whereValue = $whereCondition= "";

            foreach ($resultArray as $row) {

               $whereField = $this->tableName.".`".$row['0']."`";

                if ($row['0'] === null) {
                    $whereField = '';
                }

                $whereCondition  = (is_string($row['1'])) ? strtoupper($row['1']) : $row['1'] ;

                if (preg_match('/#/', $row['2'])) {
                    $whereValue = str_replace('#', '(', $row['2']).')';
                } else {
                    $whereValue  =  (is_string($row['2'])) ? " '".$row['2']."'" :  $row['2'] ;
                }

                $whereType = '';
                $this->_fromWhere .= $whereField." ".$whereCondition.$whereValue;

                $whereType = ($where == '' && $type !== 'OR') ? ' AND ' : ' '.$type.' ';
                $this->_fromWhere .= ($i < $arrayCount-1) ? $whereType  :  '';
                $this->_whereType = '';

                $i++;

            }

            return $this;
        }



        if (is_string($columnName)) {
            $columnName = "`".$columnName."`";
        }

        $this->_whereType = '=';
        $this->_columnWhere = $this->tableName.'.'.$columnName;
        $this->_fromWhere = " '".$where."' ";

        if (!is_null($type)) {
            $this->_whereType  = $type;
        }

        return $this;
    }
    /*
     * Extract user conditions from array
     * @access private
     * @param $arr array to extract conditions
     * @return array
     */
    private function extractConditions($arr)
    {
        //$pattern  = '/([A-Za-z_]+[A-Za-z_0-9]?)[ ]?(!=|=|<=|<|>=|>|like|clike|slike|not
        //              |is|in|between|and|or|IN|NOT|BETWEEN|LIKE|AND|OR)/';
        $pattern  = '/([\w]+)?[\s]?([\!\<\>]?\=|[\<\>]|[cs]{0,1}like|not
                    |i[sn]|between|and|or)?/i';

        $result = array();
        foreach ($arr as $key => $value) {

            preg_match($pattern, $key, $matches);
            $matches[1] = !empty($matches[1]) ? $matches[1] : null;
            $matches[2] = !empty($matches[2]) ? $matches[2] : null;

            $result []= array($matches[1], $matches[2], $value);

        }

        return $result;
    }

    /*
     * Get the distinct value of the column
     * @access public
     * @param $column
     * @return $this
     *
     */
    public function distinct($column)
    {
        $this->distinct = (string) (strtolower(__FUNCTION__).($column));

        return $this;
    }

    /*
    * limit function to limit the database query
    * @access   public
    * @param    int
    * @return   object
    */
    public function limit($limit, $offset = "")
    {
        if ($limit == ' ' || is_null($limit)) {
            throw new \Exception('Empty parameter given to limit clause ');
        }

        if (empty($offset) && !empty($limit)) {
            $this->_limitValue = 0;
            $this->_offsetValue = intval($limit);
        } else {
            $this->_limitValue = intval($limit);
            $this->_offsetValue = intval($offset);
        }

        return $this;
    }

    /*
    * Group By function to group columns based on aggregate functions
    * @access   public
    * @param    string
    * @return   object
    */
    public function groupBy($column)
    {
        if (is_null($column)) {
            throw new \InvalidArgumentException("Cannot pass null argument to ".__METHOD__);
        }

        $groupBy = "";
        switch ($column) {
            case is_array($column):
                $i = 0;
                  $count = count($column);
                while ($i < $count) { //Create group by in condition with and if value is passed as array
                        $groupBy .= '`'.$column[$i].'`';
                        $groupBy .= ($i < $count-1) ? ',' : '';
                        $i ++;
                }
                $this->_groupBy = 'GROUP BY '.$groupBy;

                return $this;
            break;
            default:
                  $this->_groupBy = 'GROUP BY `'.$column.'` ';//exit;

                return $this;
            break;
        }
    }

    public function quoteStrings($string)
    {
        //escape strings

    }

    /*
    * orderBy function to make order for selected query
    * @access   public
    * @param    string
    * @param    string
    * @return   object
    */
    public function orderBy($filedName, $orderType = "ASC")
    {
        if (empty($filedName)) {
            throw new \Exception('Empty parameter given to order by clause');
        }

        if ($this->_columnName === null && $this->_orderType === null) {
            $this->_columnName = $filedName;
        }
        $this->_orderType = $orderType;

        return $this;
    }
    /*
    * Convert array results to json encoded format
    * @access   public
    * @return   object
    */
    public function toJson()
    {
        $this->serialize = 'json';

        return $this;
    }
    /*
    * Convert array results to simple xml format
    * @access   public
    * @return   object
    */
    public function toXML()
    {
        $this->serialize = 'xml';

        return $this;
    }

    /**
     * Build and Find all the matching records from database.
     * By default its returns class with properties values
     * You can simply pass fetchMode into findAll to get various
     * format output.
     *
     * @access   public
     * @param  string $fetchMode
     * @throws \Exception
     * @internal param string $fetchMode
     * @return array or object
     */
    public function findAll($fetchMode = "")
    {
        $data = array();
        $limit = "";

        if (is_null($this->_selectColumns)) {
            $this->_selectColumns = '*';
        }

        $groupBy =(isset($this->_groupBy) && !is_null($this->_groupBy)) ?
                   $this->_groupBy :
                   '';

        $limit =  (isset($this->_limitValue)  && isset($this->_offsetValue)) ?
               " LIMIT ".$this->_limitValue.",".$this->_offsetValue." "  :  '';

        $orderBy= (isset($this->_columnName)  && isset($this->_orderType)) ?
               " ORDER BY `".$this->_columnName."`  ".$this->_orderType  :  '';

          $this->buildQuery($groupBy, $orderBy, $limit);

        try {
             $statement = $this->getDatabaseConnection()->prepare($this->sqlQuery);
             $this->setDbStatement($this->database, $statement);
             $statement->bindValue(':where', $this->_fromWhere);
             $statement->execute();
             $data = $this->fetchAs($statement, $fetchMode);

            if ($statement->rowCount() > 0) {
                return new \ArrayObject($data);
            }

        } catch (PDOException $ex) {
            throw new \Exception("Database exceptions: Invalid query x".$ex->getMessage());
        }
    }

    /*
     * fetch data as user defined format
     *
     * @access private
     * @param object $statement
     * @param string $fetchMode null
     * @return mixed.
     */
    public function fetchAs($statement, $fetchMode = null)
    {
        $data = array();

        switch ($fetchMode) {
            case 'GROUP':
                $data = $statement->fetchAll(\PDO::FETCH_GROUP| \PDO::FETCH_ASSOC);
                break;
            case 'BOTH':
                $data = $statement->fetchAll(\PDO::FETCH_BOTH);
                break;
            case 'JSON':
                $data = json_encode($statement->fetchAll(\PDO::FETCH_ASSOC));
                break;
            case 'OBJ':
                $data = $statement->fetchAll(\PDO::FETCH_OBJ);
                break;
            case 'ASSOC':
                $data = $statement->fetchAll(\PDO::FETCH_ASSOC);
                break;
            case 'COLUMN':
                $data = $statement->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'CLASS':
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, '\\'.__NAMESPACE__.'\\DataSource');
                break;
            default:
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, get_called_class());
        }

        return $data;

    }

    /*
     * Get number of rows returned by query
     *
     * @access public
     * @return int.
     */
    public function rowCount()
    {
        $statement = $this->getDbStatement($this->database);
        return $statement->rowCount();
    }

    /*
    * Build your query internally here
    *
    * @access private
    * @param  $groupBy column name
    * @param  $orderBy sort column
    * @param  $limit limit your query results
    * @return void
    */
    private function buildQuery($groupBy, $orderBy, $limit)
    {
        $searchKey = strpos($this->_fromWhere, 'AND');


        if (method_exists($this, 'exceptColumns')) {
            $ar = $this;
            $select = Schema::instance(
                $this,
                function ($table) use ($ar) {
                    $table->database =  $ar->database;
                    $table->tableName = $ar->tableName;

                    return $table->getColumns();
                }
            );

            $columns = $this->query($select->schema)->getAll();

            // Get all column name which need to remove from the result set
            $exceptColumns = $this->exceptColumns();

            foreach ($columns as $key => $value) {

                if (!in_array($value->column_name, $exceptColumns)) {
                    $columnArray[] = $value->column_name;
                }
            }
            $this->_selectColumns = (string) implode (',', $columnArray);
        }

        if ($searchKey === false) {
            ($this->_columnWhere)
                ?
                   $where = '  WHERE  '.$this->_columnWhere.' =  :where '
                :  $where = ' ';

            $where = (is_null($this->_columnWhere) && is_null($this->_fromWhere))
                ? ''
                : ' WHERE  '.$this->_columnWhere." $this->_whereType ".$this->_fromWhere."";

            $this->debugQuery =
            "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'`'.$where.
                                ' '.$groupBy.' '.$orderBy.' '.$limit;
            $this->sqlQuery =
            "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.
                                ' '.$groupBy.' '.$orderBy.' '.$limit;

        } else {

            $where = ($this->_fromWhere !="") ?
                " WHERE ".$this->_fromWhere :
                '';
             $this->debugQuery =
             "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.' '.
                $groupBy.' '.$orderBy.' '.$limit;
            $this->sqlQuery =
            "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.' '.
                $groupBy.' '.$orderBy.' '.$limit;
        }
    }

    ########

     /**
      * Build raw queries
      *
      * @access public
      * @param  string $sql
      * @param array   $attributes
      * @throws \Exception|\PDOException
      * @return object pointer $this
      */
     public function query($sql, $attributes = array())
     {
         try {
             $this->_statement = $this->getDatabaseConnection()->prepare($sql);

             if (!empty($attributes)) {
                 $this->_statement->execute($attributes);
             } else {
                 $this->_statement->execute();
             }

         } catch (\PDOException $e) {
             throw new Exception($e->getMessage());
         }

         return $this;
     }

    /*
    * Execute user raw queries
    *
    * @access public
    * @return array results
    */
    public function execute()
    {
        return $this->_statement->execute();
    }

    /*
    * Find single row
    *
    * @access public
    * @return array results
    */
    public function fetch()
    {
        return $this->_statement->fetch();
    }

     /**
      * get all rows of table
      *
      * @access   public
      * @internal param \Cygnite\Database\fetch $fetchModel type
      * @return array results
      */
     public function getAll()
     {
         return $this->_statement->fetchAll(\PDO::FETCH_CLASS, get_called_class());
     }

    /**
     * @param string     $req       : the query on which link the values
     * @param array      $array     : associative array containing the values ??to bind
     * @param array|bool $typeArray : associative array with the desired value for its
     *                                corresponding key in $array
     * @link http://us2.php.net/manual/en/pdostatement.bindvalue.php#104939
     */
    public function bindArrayValue($req, $array, $typeArray = false)
    {
        if (is_object($req) && ($req instanceof \PDOStatement)) {
            foreach ($array as $key => $value) {
                if ($typeArray) {
                    $req->bindValue(":$key", $value, $typeArray[$key]);
                } else {
                    if (is_int($value)) {
                        $param = \PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $param = \PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $param = \PDO::PARAM_NULL;
                    } elseif (is_string($value)) {
                        $param = \PDO::PARAM_STR;
                    } else {
                        $param = false;
                    }

                    if ($param) {
                        $req->bindValue(":$key", $value, $param);
                    }

                }
            }
        }
    }

    public function setDebug($sql)
    {
        $this->data[] = $sql;
    }

    protected function debugLastQuery($sql)
    {
        //echo $sql;
    }

     public function setDatabaseConnection($connection)
     {
         $this->pdo = $connection;
     }

     /**
      * Get Database Connection
      *
      * @return null|object
      */
     public function getDatabaseConnection()
     {
         return is_object($this->pdo) ? $this->pdo : null;
     }



    /*
    * Flush results after data retrieving process
    * It will unset all existing properties and close reader in order to make new selection process
    *
    */
    public function flush()
    {
        if ($this->isClosed() == false):
            $this->close();
            $this->closed = false;
            unset($this->_selectColumns);
            unset($this->_fromWhere);
            unset($this->_columnWhere);
            unset($this->_columnWhere_in);
            unset($this->_limitValue);
            unset($this->_columnName);
            unset($this->_offsetValue);
            unset($this->_orderType);
        endif;
    }

    private function setDbStatement($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getDbStatement($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : null;
    }

    /**
    * Closes the reader.
    * This frees up the resources allocated for executing this SQL statement.
    * Read attempts after this method call are unpredictable.
    */
    public function close()
    {
        $statement = null;
        $statement = $this->getDbStatement($this->database);

        $statement->closeCursor();
        $this->closed = true;
    }

    /**
    * whether the reader is closed or not.
    * @return boolean whether the reader is closed or not.
    */
    private function isClosed()
    {
        return $this->closed;
    }

    /*public function getModelProperties()
    {
        echo $this->tableName;
        echo $this->database;
        echo $this->primaryKey;
        var_dump($this->columns);
    }*/

    public function explainQuery()
    {
        $sql = $explain = "";
        $sql = 'EXPLAIN EXTENDED '.$this->sqlQuery;
        $explain = $this->getDatabaseConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $html = "";
        $html  .= "<html> <head><title>Explain Query</title>
                           <style type='text/css'>
                           #contetainer { font-family:Lucida Grande,Verdana,Sans-serif;
                           font-size:12px;padding: 20px 20px 12px 20px;margin:40px; background:#fff;
                           border:1px solid #D3640F; }
                           h2 { color: #990000;  font-size: 15px;font-weight: normal;margin: 5px 5px 5px 13px;}
                           p {   margin:6px; padding: 9px; }
                           </style>
                           </head><body>
       <div id='contetainer'>
            <table >
            <th>ID</th>
            <th>Query Type</th>
            <th>Table</th>
            <th>Type</th>
            <th>Possible Keys</th>
            <th>Key</th>
            <th>Key Length</th>
            <th>Ref</th>
            <th>Rows</th>
            <th>Filtered</th>
            <th>Extra</th>
            <tr>
            <td> ".$explain[0]['id']."</td>
            <td> ".$explain[0]['select_type']."</td>
            <td> ".$explain[0]['table']."</td>
            <td> ".$explain[0]['type']."</td>
            <td> ".$explain[0]['possible_keys']."</td>
            <td> ".$explain[0]['key']."</td>
            <td> ".$explain[0]['key_len']."</td>
            <td> ".$explain[0]['ref']."</td>
            <td> ".$explain[0]['rows']."</td>
            <td> ".$explain[0]['filtered']."</td>
            <td> ".$explain[0]['Extra']."</td></tr></table></div></body></html>";
       unset($explain);
        return $html;

    }

    public function setPageLimit($number = null)
    {
        if (is_null($number)) {
            $number = $this->setPageLimit();
        }

        $pagination = Pagination::instance();
        $pagination->setPerPage($number);
    }

    public function __destruct()
    {
        unset($this->attributes);
        unset($this->data);
    }
}
