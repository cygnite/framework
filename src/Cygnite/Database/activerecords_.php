<?php
namespace Cygnite\Database;

use Cygnite\Database\Connections;
use Cygnite\Database\Configurations;
use Cygnite\Database\Exceptions\DatabaseException;
use PDO;
use PDOException;
use Cygnite\Inflectors;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
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
 * @Filename                  :  ActiveRecords
 * @Description               :  Avctive records to handle database manipulations. As like
 *                               Read, write, erase, update etc.
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @Filesource
 * @Warning                   :  Any changes in this library can cause abnormal behaviour of the framework
 *
 */

abstract class ActiveRecords extends Connections
{
    public $id;
    //Hold your connection object
    public $pdo;

    public $modelClass;

    //set closed property as true is set else false
    public $closed;

    //Hold all your table fields in attributes
    //public $attributes = array();

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

    // set query builder query into property
    private $sqlQuery;

    private $debugQuery;

    private $distinct;

    /*
     * Restrict users to create active records object Directly
     * Get the database configurations
     *
     */
    protected function __construct()
    {
        $config = Configurations::instance();

        $this->modelClass = get_class($this);

        if (is_null($this->database)) {
            throw new \InvalidArgumentException(
                "Please specify database in your model.".get_called_class()
            );
        }

        if (is_null($this->tableName)) {
            throw new \InvalidArgumentException(
                "Please specify Table Name in your model.".get_called_class()
            );
        }

        foreach ($config->connections as $key => $value) {

            if (preg_match('/'.$this->database.'/', $value, $m)) { echo "her connect";
                $this->pdo = $this->setConnection($value);
            }
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
        $model = null;
        //$model = static::getDynamicInstance();
        //$model->{$key} = $value;

        foreach ($this->attributes as $key => $value) {
            $this->{$key} = $value;
        }
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
            throw new $ex->getMessage();
        }
    }

    public static function __callStatic($method, $arguments)
    {

        //var_dump(strstr($method, 'findBy'));

        if (substr($method, 0, 6) == 'findBy') {
            show(substr($method, 6));
            $class = self::getDynamicInstance();
            var_dump($class->primaryKey);
            //$class->primaryKey = 1;
            return $class;
        }
        show($method);
        if ($method == 'find') {
            $class = self::getDynamicInstance();
            call_user_func_array(array($class, $method), $arguments);
            //var_dump($class->primaryKey);

            //exit;
            //$class->primaryKey = 1;
            return $class;
        }

        //show($arguments);

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
        if ($method == 'save') {

            var_dump($this->isNew());

            if (empty($arguments) && $this->isNew() == true) {
                if (method_exists($this, 'saveInTable')) {
                    return call_user_func_array(array($this, 'saveInTable'), $arguments);
                }
            } else {

                //echo $model = get_called_class();
                //$mode = static::getDynamicInstance();

                //show($this->attributes);

                // var_dump((new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC));
                //exit;
                //$reflection = new ReflectionObject('GuestBook');
                // $val = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
                //show($val);
                //exit;
                if (method_exists($this, 'updateTable')) {
                    if (empty($arguments)) {
                        //echo $id = $this->primaryKey;
                        //var_dump($this->attributes);
                        $arguments[] = $this->id;

                    }

                    return call_user_func_array(array($this,'updateTable'), $arguments);
                }
            }
        }

        var_dump($method);

        if ($method == 'find') {
            $id = array_shift($arguments);
            $fetch = $this->select('*')->where($this->primaryKey, $id)->findAll();
            var_dump($this->primaryKey);
            $this->id = $this->primaryKey;
            $this->id = $id;

            if ($fetch == null) {
                $class = self::getDynamicInstance();
                $this->id = null;
                return new $class;
            }

            //show($fetch[0]->attributes);
            //$attributes = (object) $fetch[0]->attributes;
            //show($attributes);

            foreach ($fetch[0]->attributes as $key => $value) {
                $this->{$key} = $value;
            }

            $this->assignPropertiesToModel($this->attributes);
            $data = $this->attributes;

            return $data;

            //Inflectors
        }

        if (substr($method, 6)) {
            show($method);
            //Inflectors
        }
        exit;


        //throw new \Exception("Invalid method $name called  ");
    }

    protected function assignPropertiesToModel($attributes = array())
    {
        $model = null;

        //var_dump($this->attributes);

        foreach ($attributes as $key => $value) {
            //$this->{$key} = $value;
            $model = self::getDynamicInstance();
            $model->{$key} = $value;
            //$model->attributes[$key] = $value;
            //var_dump($model->$key);
        }
    }

    public function isNew()
    {
        return ($this->id == null) ? true : false;
    }

    private function findBy($key, $values = array())
    {

        echo $key;
    }
    /*
     * Save data into table
     * @access private
     * @param $arguments empty array
     * @return true
     *
     */
    private function saveInTable($arguments = array())
    {
        $fields = $values = array();
        $query = $debugQuery = "";

        foreach (array_keys($this->attributes) as $key) {

             $fields[] = "`$key`";
             $values[] = "'" .$this->attributes[$key] . "'";
             $placeholder[] = substr(str_repeat('?,', count($key)), 0, -1);
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);
        $placeHolders = implode(',', $placeholder);

        $query = "INSERT INTO `".$this->database."`.`".$this->tableName."`
           ($fields) VALUES"." ($placeHolders)".";";

        $debugQuery = "INSERT INTO `".$this->database."`.`".$this->tableName."`
           ($fields) VALUES"." ($values)".";";
        //$this->setdDebugQuery($query);

        //show(array_values($this->attributes));

        try {
            //$this->pdo->quote($string, $parameter_type=null); have to write a method to escape strings
            $statement = $this->pdo->prepare($query);
            $statement->execute(array_values($this->attributes));

            return true;

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
    private function updateTable($args)
    {
        $query  =$debugQuery= $x = "";
        $updateBy = $updateValue = null;

        if ((is_array($args) && !empty($args) )) {
            $x = array_keys($args);
            $updateBy = $x[0];
            $updateValue = $args[$x[0]];
        } else {
            $updateBy = $this->primaryKey;
            $updateValue = $args;
        }


        $query .="UPDATE `".$this->database."`.`".$this->tableName."` SET ";
        $debugQuery .="UPDATE `".$this->database."`.`".$this->tableName."` SET ";
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
            echo $debugQuery .=" WHERE ".$updateBy." = ".$updateValue;

            //$this->debugLastQuery($debugQuery);
        try {
            $statement = $this->pdo->prepare($query);
            $statement->bindValue(':column', $updateValue);

            return $statement->execute();

        } catch (\PDOException  $exception) {
               echo  $exception->getMessage();
        }
    }

    /**
     * Trash method
     *
     * Delete rows from the table and runs the query
     *
     * @access    public
     * @param array $where
     * @throws Exceptions\DatabaseException
     * @internal  param \Cygnite\Database\the $string table to retrieve the results from
     * @return object
     */
    public function trash($where)
    {
        $whr = array();
        $column = $value = null;

        if (is_array($where)) {
            $whr = array_keys($where);
            $column = $whr[0];
            $value = $where[$whr[0]];
        } else {
            $column = $this->primaryKey;
            $value = $where;
        }

        $sqlQuery = "DELETE FROM `".$this->tableName."` WHERE `".$column."` = :where";
        $debugQuery = "DELETE FROM `".$this->tableName."` WHERE `".$column."` = ".$value;

        /** @var $exception TYPE_NAME */
        try {
            $statement = $this->pdo->prepare($sqlQuery);
            
            $statement->bindValue(':where', $value);

            return $statement->execute();

        } catch (\PDOException  $exception) {
            throw new DatabaseException($exception);
        }

    }

    /**
    * Find Function to selecting Table columns
    *
    * Generates the SELECT portion of the query
    *
    * @access	public
    * @param	string
    * @return	object
    */
    public function select($type)
    {
        //create where condition with and if value is passed as array
        if (is_string($type) && !is_null($type)) {
            if ($type === 'all' || $type == '*') {
                $this->_selectColumns = '*';
            } else {
                $this->_selectColumns = (string) $type; // Need to split the column name and add quotes
            }
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

                $whereField = "`".$row['0']."`";

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
                $whereType = ($where == '') ? ' AND' : ' '.$where.' ';
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
        $this->_columnWhere = $columnName;
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
        $this->distinct = "DISTINCT($column)";

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

    public function quote()
    {
        // escape strings
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

        $groupBy =(isset($this->_groupBy) && !is_null($this->_groupBy)) ? $this->_groupBy : '';

        $limit =  (isset($this->_limitValue)  && isset($this->_offsetValue)) ?
               " LIMIT ".$this->_limitValue.",".$this->_offsetValue." "  :  '';

        $orderBy= (isset($this->_columnName)  && isset($this->_orderType)) ?
               " ORDER BY `".$this->_columnName."`  ".$this->_orderType  :  '';

          $this->buildQuery($groupBy, $orderBy, $limit);

        try {
             $statement = $this->pdo->prepare($this->sqlQuery);
             $this->setDbStatement($this->database, $statement);
             $statement->bindValue(':where', $this->_fromWhere);
             $statement->execute();
             $data = $this->fetchAs($statement, $fetchMode);

            if ($statement->rowCount() > 0) {
                return $data;
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
     * @param string $fetchMode
     * @return mixed.
     */
    private function fetchAs($statement, $fetchMode)
    {
        $data = array();

        switch ($fetchMode) {
            case 'GROUP':
                $data = $statement->fetchAll(PDO::FETCH_GROUP| PDO::FETCH_ASSOC);
                break;
            case 'BOTH':
                $data = $statement->fetchAll(PDO::FETCH_BOTH);
                break;
            case 'JSON':
                $data = json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
                break;
            case 'OBJ':
                $data = $statement->fetchAll(PDO::FETCH_OBJ);
                break;
            case 'ASSOC':
                $data = $statement->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'COLUMN':
                $data = $statement->fetchAll(PDO::FETCH_COLUMN);
                break;
            case 'CLASS':
                $data = $statement->fetchAll(PDO::FETCH_CLASS, '\\'.__NAMESPACE__.'\\Datasource');
                break;
            default:
                $data = $statement->fetchAll(PDO::FETCH_CLASS, get_called_class());
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

        if ($searchKey === false) {
            ($this->_columnWhere)
                ?
                   $where = '  WHERE  '.$this->_columnWhere.' =  :where '
                :  $where = ' ';

            $where = (is_null($this->_columnWhere) && is_null($this->_fromWhere))
                ? ''
                : ' WHERE  '.$this->_columnWhere." $this->_whereType ".$this->_fromWhere."";

            $this->debugQuery = "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'`'.$where.
                                ' '.$groupBy.' '.$orderBy.$limit;
            $this->sqlQuery = "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.
                                ' '.$groupBy.' '.$orderBy.$limit;
        } else {

            ($this->_fromWhere !="")
                ?
                $where = " WHERE ".$this->_fromWhere
                :
                $where = "";
             $this->debugQuery = "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.' '.
                $groupBy.' '.$orderBy.$limit;
            $this->sqlQuery = "SELECT ".$this->_selectColumns." FROM `".$this->tableName.'` '.$where.' '.
                $groupBy.' '.$orderBy.$limit;
        }
    }

    ########

    /*
    * Build user raw query
    *
    * @access public
    * @param  string $sql
    * @return object pointer $this
    */
    public function query($sql)
    {
        $this->_statement = $this->pdo->query($sql);

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

    /*
    * get all rows of table
    *
    * @access public
    * @param  $fetchModel fetch type
    * @return array results
    */
    public function getAll($fetchMode = PDO::FETCH_OBJECT)
    {
        $data = array();
        ob_start();
        $data  = $this->_statement->fetchAll($fetchMode);

        ob_end_clean();
        ob_end_flush();

        return $data;
    }

    /**
     * @param string     $req       : the query on which link the values
     * @param array      $array     : associative array containing the values ??to bind
     * @param array|bool $typeArray : associative array with the desired value for its corresponding key in $array
     */
    public function bindArrayValue($req, $array, $typeArray = false)
    {
        if (is_object($req) && ($req instanceof PDOStatement)) {
            foreach ($array as $key => $value) {
                if ($typeArray) {
                    $req->bindValue(":$key", $value, $typeArray[$key]);
                } else {
                    if (is_int($value)) {
                        $param = PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $param = PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $param = PDO::PARAM_NULL;
                    } elseif (is_string($value)) {
                        $param = PDO::PARAM_STR;
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
        echo $sql;
    }

    public function getConnection($connection)
    {
        return is_resource($this->pdo) ? $this->pdo : null;
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

    public function getModelProperties()
    {
        echo $this->tableName;
        echo $this->database;
        echo $this->primaryKey;
        var_dump($this->columns);

        /*$object = new ReflectionClass(get_class($this));
        $ob =  get_class($this);
        echo $object->getProperty('tableName')->getValue(new $ob);
        echo "<pre>";
        var_dump($object);
        echo "</pre>"; */

    }

    public function explainQuery()
    {
        $sql = $explain = "";
        $sql = 'EXPLAIN EXTENDED '.$this->sqlQuery;
        $explain = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $html = "";
        $html  .= "<html> <head><title>Explain Query</title>
                           <style type='text/css'>
                             #contetainer { font-family:Lucida Grande,Verdana,Sans-serif; font-size:12px;padding: 20px 20px 12px 20px;margin:40px; background:#fff; border:1px solid #D3640F; }
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
        echo $html;//exit;
        unset($explain);
    }

    public function __destruct()
    {
        unset($this->attributes);
        unset($this->data);
    }
}
