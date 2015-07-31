<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database\Query;

use PDO;
use Cygnite;
use Exception;
use PDOException;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Pagination;
use Cygnite\Foundation\Collection;
use Cygnite\Database\ConnectionManagerTrait;
use Cygnite\Database\Cyrus\ActiveRecord;

/**
 * Class Builder
 *
 * @package Cygnite\Database\Query
 */
class Builder extends Joins implements QueryBuilderInterface
{
    use ConnectionManagerTrait;

    const DELETE = 'DELETE';

    //hold all your fields name which to select from table
    const LIMIT_STYLE_TOP_N = "top";
    const LIMIT_STYLE_LIMIT = "limit";
    public static $query;
    private static $debugQuery = [];
    private static $activeRecord;
    private static $cacheData = [];
    private static $dataSource = false;
    public $data = [];
    protected $_tableAlias;
    protected $pdo = [];
    private $_statement;

    // set query builder query into property
    private $_selectColumns;
    private $_limitValue;
    private $_offsetValue;

    //Hold your pdo connection object
    private $_columnName;
    private $_orderType;

    // Limit clause style
    private $_groupBy;
    private $distinct;
    private $sqlQuery;
    private $closed;

    private $fromTable;
    /**
     * @access private
     * @var Array $where
     */
    private $where = [];
    /**
     * Bindings for where sql statement
     *
     * @access private
     * @var Array $bindings
     */
    private $bindings = [];

    public static $queries = [];

    /**
     * You can not instantiate the Query object directly
     *
     * @param ActiveRecord $instance
     */
    public function __construct(ActiveRecord $instance)
    {
        if ($instance instanceof ActiveRecord) {
            $this->setActiveRecord($instance);
        }

        self::$query = $this;
    }

    /**
     * Get Cyrus ActiveRecord instance
     *
     * @return null
     */
    public static function cyrus()
    {
        return is_object(self::$activeRecord) ? self::$activeRecord : null;
    }

    public static function make(\Closure $callback, $activeRecord = null)
    {
        return $callback(new Builder($activeRecord));
    }

    public static function _callMethod(\Closure $callback, $instance = null)
    {
        return $callback(self::$query, $instance);
    }

    /**
     * We will set Database Connection object
     *
     * @param $connection
     */
    public function setDatabaseConnection($connection)
    {
        $this->pdo[$connection] = $this->getConnection($connection);
    }

    /**
     * Get Database Connection Object based on database name
     * provided into model class
     *
     * @return null|object
     */
    public function getDatabaseConnection()
    {
        $connection = self::cyrus()->getDatabase();

        $this->setDatabaseConnection($connection);

        return is_object($this->pdo[$connection]) ? $this->pdo[$connection] : null;
    }

    /**
     * @param array $arguments
     * @return mixed
     * @throws \RuntimeException
     */
    public function insert($arguments = [])
    {
        $sql = $ar = null;
        $ar = self::cyrus();
        $this->triggerEvent('beforeCreate');
        $sql = $this->getInsertQuery(strtoupper(__FUNCTION__), $ar, $arguments);
        try {
            $statement = $this->getDatabaseConnection()->prepare($sql);
            // we will bind all parameters into the statement using execute method
            if ($bool = $statement->execute($arguments)) {
                $ar->{$ar->getKeyName()} = (int)$this->getDatabaseConnection()->lastInsertId();
                $this->triggerEvent('afterCreate');

                return $bool;
            }
        } catch (PDOException  $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    /**
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function update($args)
    {
        $column = $value = $x = null;
        $ar = self::cyrus();
        $this->triggerEvent('beforeUpdate');

        if (is_array($args) && !empty($args)) {
            $x = array_keys($args);
            $column = $x[0];
            $value = $args[$x[0]];
        } else {
            $column = $ar->getKeyName();
            $value = $args;
        }

        $sql = $this->getUpdateQuery($column, strtoupper(__FUNCTION__));
        try {
            $statement = $this->getDatabaseConnection()->prepare($sql);
            //Bind all values to query statement
            foreach (array_merge($ar->attributes, [$column => $value]) as $key => $val) {
                $statement->bindValue(":$key", $val);
            }

            $affectedRow = $statement->execute();
            $this->triggerEvent('afterUpdate');

            return $affectedRow;
        } catch (\PDOException  $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Trash method
     *
     * Delete rows from the table and runs the query
     *
     * @access    public
     * @param  array $where
     *                              $multiple false
     * @param  bool  $multiple
     * @throws \Exception
     * @internal  param \Cygnite\Database\the $string table to retrieve the results from
     * @return object
     */
    public function trash($where = null, $multiple = false)
    {
        $whr = [];
        $statement = $affectedRows = null;
        $ar = self::cyrus();
        $this->triggerEvent('beforeDelete');

        // Bind where conditions
        $this->bindWhereClause($where, $multiple, $ar);

        $sql = self::DELETE .
            " FROM " . $this->quoteIdentifier($ar->getDatabase()) . '.' . $this->quoteIdentifier($ar->getTableName())
            . $this->getWhere();

        try {
            $stmt = $this->getDatabaseConnection()->prepare($sql);
            $this->bindParam($stmt); //bind parameters
            $affectedRow = $stmt->execute();
            $this->triggerEvent('afterDelete');

            return $affectedRow;
        } catch (\PDOException  $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * <code>
     * $user->trash(1);
     * $user->trash([1,4,6,33,54], true)
     * $user->trash(['id' => 23])
     * $user->where('name', '=', 'application')->trash();
     * </code>
     * @param $where
     * @param $multiple
     * @param $ar
     */
    private function bindWhereClause($where, $multiple, $ar)
    {
        /*
         | Check if array given as where parameter, then
         | we will bind parameters into whereIn conditions
         */
        if (is_array($where) && $multiple == false) {
            $whr = array_keys($where);
            $this->where($this->quoteIdentifier($whr[0]), '=', $where[$whr[0]]);
        } elseif (is_array($where) && $multiple == true) {
            $this->whereIn($this->quoteIdentifier($ar->getKeyName()), 'IN', implode(',', $where));
        }

        /*
         | Check if string | int given as where parameter
         */
        if (is_string($where) || is_int($where)) {
            $this->where($this->quoteIdentifier($ar->getKeyName()), '=', $where);
        }
    }


    /**
     * Adding an element in the where array with the value
     * to the bindings
     *
     * @access public
     * @param String $key
     * @param String $operator
     * @param String $value
     * @return void
     */
    public function where($key, $operator, $value)
    {
        if (strpos($operator, 'IN') !== false) {
            return $this->whereIn($key, $operator, $value);
        }

        $this->where[] = "AND " . $key . ' ' . $operator . ' ' . "?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Adding an element in the where array with the value
     * to the bindings
     *
     * @access public
     * @param String $key
     * @param String $operator
     * @param String $value
     * @return void
     */
    public function whereIn($key, $operator, $value)
    {
        $exp = explode(',', $value);
        $this->where[] = "AND " . $key . ' ' . $operator . ' (' . $this->createPlaceHolder($exp) . ') ';

        foreach ($exp as $key => $val) {
            $this->bindings[$key] = $val;
        }

        return $this;
    }

    /**
     * Adding an element in the where array with the value
     * to the bindings
     *
     * @access public
     * @param String $key
     * @param String $operator
     * @param String $value
     * @return void
     */
    public function orWhere($key, $operator, $value)
    {
        $this->where[] = "OR " . $key . ' ' . $operator . ' ' . "?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * @param        $key
     * @param        $value
     * @param string $operator
     * @return $this
     */
    public function orWhereIn($key, $value, $operator = 'IN')
    {
        $exp = explode(',', $value);
        $this->where[] = "OR OR" . $key . ' ' . $operator . ' (' . $this->createPlaceHolder($exp) . ') ';

        foreach ($exp as $key => $val) {
            $this->bindings[$key] = $val;
        }

        return $this;
    }

    /**
     * Get the distinct value of the column
     *
     * @access public
     * @param $column
     * @return $this
     *
     */
    public function distinct($column)
    {
        $this->distinct = (string)(strtolower(__FUNCTION__) . ($column));

        return $this;
    }

    public function limit($limit, $offset = "")
    {
        if (is_null($limit)) {
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

    /**
     * orderBy function to make order for selected query
     *
     * @param        $column
     * @param string $orderType
     * @return $this
     * @throws \Exception
     */
    public function orderBy($column, $orderType = "ASC")
    {
        if (empty($column) || is_null($column)) {
            throw new \Exception('Empty parameter given to order by clause');
        }
        $this->_orderType = $orderType;

        if (is_array($column)) {
            $this->_columnName = $this->extractArrayAttributes($column);
            return $this;
        }

        if (is_null($this->_columnName)) {
            $this->_columnName = $column;
        }

        return $this;
    }

    /**
     * Group By function to group columns based on aggregate functions
     *
     * @param $column
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function groupBy($column)
    {
        if (is_null($column)) {
            throw new \InvalidArgumentException("Cannot pass null argument to " . __METHOD__);
        }

        if (is_array($column)) {
            $this->_groupBy = $this->extractArrayAttributes($column);
            return $this;
        }

        $this->_groupBy = $this->quoteIdentifier($column);
        return $this;
    }

    /**
     * Add an alias for the main table to be used in SELECT queries
     */
    public function tableAlias($alias)
    {
        $this->_tableAlias = $alias;

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
     * @return array      or object
     */
    public function findAll($fetchMode = "")
    {
        $data = [];
        $ar = self::cyrus();
        $this->triggerEvent('beforeSelect');

        $this->buildQuery();
        try {
            $statement = $this->getDatabaseConnection()->prepare($this->sqlQuery);
            $this->sqlQuery = null;
            $this->setDbStatement($ar->getTableName(), $statement);
            $this->bindParam($statement);
            $statement->execute();
            $data = $this->fetchAs($statement, $fetchMode);
            $this->triggerEvent('afterSelect');

            if ($statement->rowCount() > 0) {
                return new Collection($data);
            } else {
                return new Collection([]);
            }
        } catch (PDOException $ex) {
            throw new \Exception("Database exceptions: Invalid query x" . $ex->getMessage());
        }
    }

    public function fetchAs($statement, $fetchMode = null)
    {
        $data = [];

        if (static::$dataSource) {
            $fetchMode = 'class';
            static::$dataSource = false;
        }

        switch ($fetchMode) {
            case 'group':
                $data = $statement->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
                break;
            case 'both':
                $data = $statement->fetchAll(\PDO::FETCH_BOTH);
                break;
            case 'json':
                $data = json_encode($statement->fetchAll(\PDO::FETCH_ASSOC));
                break;
            case 'object':
                $data = $statement->fetchAll(\PDO::FETCH_OBJ);
                break;
            case 'assoc':
                $data = $statement->fetchAll(\PDO::FETCH_ASSOC);
                break;
            case 'column':
                $data = $statement->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'class':
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, "\\Cygnite\\Database\\DataSource");
                break;
            default:
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, '\\' . self::cyrus()->getModelClassNs());
                break;
        }

        return $data;
    }

    public function getGroupBy()
    {
        return (isset($this->_groupBy) && !is_null($this->_groupBy)) ?
            'GROUP BY ' . $this->_groupBy : '';
    }

    public function getOrderBy()
    {
        return (isset($this->_columnName) && isset($this->_orderType)) ?
            " ORDER BY " . $this->_columnName . "  " . $this->_orderType : '';
    }

    public function getLimit()
    {
        return (isset($this->_limitValue) && isset($this->_offsetValue)) ?
            " LIMIT " . $this->_limitValue . "," . $this->_offsetValue . " " : '';
    }

    /**
     * Get row count
     *
     * @return mixed
     */
    public function rowCount()
    {
        $statement = $this->getDbStatement(self::cyrus()->getDatabase());

        return $statement->rowCount();
    }

    /**
     * @param $key
     * @return null
     */
    public function getDbStatement($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : null;
    }

    /**
     * Build raw queries
     *
     * @access public
     * @param  string $sql
     * @param  array  $attributes
     * @throws \Exception|\PDOException
     * @return object                   pointer $this
     */
    public function query($sql, $attributes = [])
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

    /**
     * @return mixed
     */
    public function execute()
    {
        return $this->_statement->execute();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->first();
    }

    /**
     * Get all rows of table as Collection
     *
     * @access   public
     * @internal param \Cygnite\Database\fetch $fetchModel type
     * @return array results
     */
    public function getAll()
    {
        return new Collection($this->fetchAs($this->_statement));
    }

    /**
     * @param string     $req       : the query on which link the values
     * @param array      $array     : associative array containing the values ??to bind
     * @param array|bool $typeArray : associative array with the desired value for its
     *                              corresponding key in $array
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

    /**
     * This method is mainly used for building where conditions as array
     * for dynamic finders.
     *
     *
     * @param $method    String
     * @param $arguments array
     * @param $type      string
     * @throws \Exception
     * @return object
     *
     */
    public function buildFindersWhereCondition($method, $arguments, $type = 'And')
    {
        $condition = [];
        $condition = explode($type, str_replace('findBy', '', $method));

        if (count($condition) == count($arguments[0])) {
            foreach ($condition as $key => $value) {
                $field = Inflector::tabilize($value);
                $whrValue = isset($arguments[0][$key]) ?
                    trim($arguments[0][$key]) :
                    '';
                if ($type == 'And') {
                    static::$query->select('all')->where($field, '=', $whrValue);
                } else {
                    static::$query->select('all')->orWhere($field, '=', $whrValue);
                }
            }
        } else {
            throw new Exception("Arguments doesn't matched with number of fields");
        }

        return static::$query;
    }

    /**
     * Find Function to selecting Table columns
     *
     * Generates the SELECT portion of the query
     *
     * @access    public
     * @param     $column
     * @throws    \Exception
     * @return     object
     */
    public function select($column)
    {
        //select columns
        if (is_string($column) && !is_null($column)) {
            return $this->_select($column);
        }

        throw new Exception("Accepted parameters should be string.");
    }

    /**
     * Add an unquoted expression to the list of columns returned
     * by the SELECT query. The second optional argument is
     * the alias to return the column as.
     */
    public function selectExpr($expr)
    {
        $this->_selectColumns = (string)$expr;

        return $this;
    }

    /**
     * Find result using raw sql query
     *
     * @param $arguments
     * @return Collection
     */
    public function findBySql($arguments)
    {
        $results = [];
        $statement = $this->getDatabaseConnection()->prepare(trim($arguments[0]));
        $statement->execute();
        $results = $this->fetchAs($statement);

        return new Collection($results);
    }

    public function from($table)
    {
        $this->fromTable = (is_null($table)) ? get_class($this) : $table;
        return $this;
    }

    /**
     * @return null
     */
    public function getFormTable()
    {
        return isset($this->formTable) ? $this->formTable : null;
    }

    /**
     * Set table to run fluent query without model class
     *
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $ar = static::cyrus();
        $ar->setTableName($table);
        static::$dataSource = true;
        return $this;
    }

    /*
     * fetch data as user defined format
     *
     * @access private
     * @param  object $statement
     * @param  string $fetchMode null
     * @return mixed.
     */

    /**
     * @param       $method
     * @param array $params
     * @return mixed
     */
    public function callFinder($method, array $params)
    {
        return $this->find($method, $params);
    }
    /**
     * Find a single row
     *
     * @param       $method
     * @param array $options
     * @return mixed
     */
    public function find($method, $options = [])
    {
        if (isset($options['primaryKey']) && $method == __FUNCTION__) {
            return $this->select('all')
                ->where(self::cyrus()->getKeyName(), '=', array_shift($options['args']))
                ->orderBy(self::cyrus()->getKeyName(), 'DESC')
                ->findAll();
        }

        return $this->{$method}($options);
    }

    /**
     * We will return last executed query
     *
     * @return string
     */
    public function lastQuery()
    {
        return end(static::$queries);
    }

    public function flush()
    {
        if ($this->isClosed() == false) {
            $this->close();
            $this->closed = false;
        }
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        $statement = null;
        $statement = $this->getDbStatement(self::cyrus()->getDatabase());

        $statement->closeCursor();
        $this->pdo = null;
        $this->closed = true;
    }

    /**
     * Set ActiveRecord instance
     *
     * @param $instance
     */
    private function setActiveRecord($instance)
    {
        self::$activeRecord = $instance;
    }

    /*
    * Execute user raw queries
    *
    * @access public
    * @return array results
    */

    /**
     * We will trigger event
     *
     * @param $event
     */
    private function triggerEvent($event)
    {
        $instance = static::cyrus();

        if (method_exists($instance, $event) &&
            in_array($event, $instance->getModelEvents())
        ) {
            $instance->{$event}($instance);
        }
    }

    /**
     * Get insert sql statement
     *
     * @access   private
     * @param $function
     * @param $ar
     * @param $arguments
     * @return string
     */
    private function getInsertQuery($function, $ar, $arguments)
    {
        $keys = array_keys($arguments);

        return $function . " INTO `" . $ar->getDatabase() . "`.`" . $ar->getTableName() .
        "` (" . implode(", ", $keys) . ")" .
        " VALUES(:" . implode(", :", $keys) . ")";
    }

    /**
     * Get update sql statement
     *
     * @access private
     * @param       $id
     * @param       $function
     * @return String
     */
    private function getUpdateQuery($id, $function)
    {
        $ar = static::cyrus();
        $sql = '';
        foreach ($ar->attributes as $key => $value) {
            $sql .= $key . '=:' . $key . ',';
        }
        $sql = rtrim($sql, ",");

        return $function . " `" . $ar->getDatabase() . "`.`" . $ar->getTableName() . "` SET " .
        " " . $sql .
        " WHERE " . $id . "=:" . $id;
    }

    /**
     * We will bind Parameters to execute queries
     *
     * @param $stmt
     */
    private function bindParam($stmt)
    {
        // Bind parameters
        for ($i = 1; $i <= count($this->bindings); $i++) {
            $stmt->bindParam($i, $this->bindings[$i - 1]);
        }
    }

    private function setDebug($sql)
    {
        static::$debugQuery[] = $sql;
    }

    /**
     * @param $arguments
     * @return string
     */
    private function createPlaceHolder($arguments)
    {
        foreach (array_keys($arguments) as $key) {
            $placeholder[] = substr(str_repeat('?,', count($key)), 0, -1);
        }
        return implode(',', $placeholder);
    }

    /**
     * @param $columns
     * @return string
     */
    private function extractArrayAttributes($columns)
    {
        $i = 0;
        $str = "";
        $count = count($columns);
        while ($i < $count) { //Create conditions with and if value is passed as array
            $str .= $this->quoteIdentifier($columns[$i]);
            $str .= ($i < $count - 1) ? ',' : '';
            $i++;
        }

        return $str;
    }

    /**
     * Build query internally
     *
     * @return $this
     */
    private function buildQuery()
    {
        // Ignore columns while selecting from database
        if (method_exists(self::cyrus(), 'skip')) {
            $this->prepareExceptColumns();
        }

        return $this->buildSqlQuery();
    }

    /**
     * we will prepare query except columns given in model
     * and assign it to selectColumns to find the results
     */
    private function prepareExceptColumns()
    {
        $ar = self::cyrus();
        // we will get the table schema
        $select = Schema::make(
            $this,
            function ($table) use ($ar) {
                $table->database = $ar->getDatabase();
                $table->tableName = $ar->getTableName();

                return $table->getColumns();
            }
        );

        $columns = $this->query($select->schema)->getAll();

        // Get all column name which need to remove from the result set
        $exceptColumns = $ar->skip();
        $columnArray = [];
        foreach ($columns as $key => $value) {
            if (!in_array($value->column_name, $exceptColumns)) {
                $columnArray[] = $value->column_name;
            }
        }
        $this->_selectColumns = (string)implode(',', $columnArray);
    }

    /**
     * @return $this
     */
    private function buildSqlQuery()
    {
        $this->sqlQuery =
            'SELECT ' . $this->buildSelectedColumns() . ' FROM ' . $this->quoteIdentifier(
                self::cyrus()->getTableName()
            ) . ' ' . $this->_tableAlias . $this->getJoinSource() . $this->getWhere() .
            ' ' . $this->getGroupBy() . ' ' . $this->getOrderBy() . ' ' . $this->getLimit();

        static::$queries[] = $this->sqlQuery;

        return $this;
    }

    /**
     * We can use this method to debug query which
     * executed last
     *
     * @return string
     */
    private function buildOriginalQuery()
    {
        return
            'SELECT ' . $this->buildSelectedColumns() . ' FROM ' . $this->quoteIdentifier(
                self::cyrus()->getTableName()
            ) . ' ' . $this->getWhere() .
            ' ' . $this->getGroupBy() . ' ' . $this->getOrderBy() . ' ' . $this->getLimit();
    }

    /**
     * @return string
     */
    private function buildSelectedColumns()
    {
        if (is_null($this->_selectColumns)) {
            $this->_selectColumns = '*';
        }

        return ($this->_selectColumns == '*') ?
            $this->quoteIdentifier(
                self::cyrus()->getTableName()
            ) . ' .' . $this->_selectColumns : $this->_selectColumns;
    }

    /**
     * @return string
     */
    private function getJoinSource()
    {
        if ($this->hasJoin) {
            $joinSource = '';
            foreach ($this->joinSources as $key => $qry) {
                $joinSource .= " $qry";
            }

            return $joinSource;
        }
    }

    /**
     * Get where statement
     *
     * @access private
     * @return String
     */
    private function getWhere()
    {
        // If where is empty return empty string
        if (empty($this->where)) {
            return '';
        }

        // Implode where pecices then remove the first AND or OR
        return ' WHERE ' . ltrim(implode(" ", $this->where), "ANDOR");
    }

    private function setDbStatement($key, $value)
    {
        $this->data[$key] = $value;
    }

    private function _select($column)
    {
        if ($column === 'all' || $column == '*') {
            $this->_selectColumns = $this->quoteIdentifier(
                    self::cyrus()->getTableName()
                ) . '.*';
        } else {
            if (string_has($column, 'as') || string_has($column, 'AS')) {
                return $this->selectExpr($column);
            }

            $this->_selectColumns = (string)str_replace(' ', '', $this->quoteIdentifier(explode(',', $column)));
        }

        return $this;
    }

    /**
     * Find all values from the database table
     *
     * @param $arguments
     * @return mixed
     */
    public function all($arguments)
    {
        if (isset($arguments[0]['orderBy'])) {
            $exp = [];
            $exp = explode(' ', $arguments[0]['orderBy']);
            $this->orderBy($this->quoteIdentifier($exp[0]), (isset($exp[1])) ? strtoupper($exp[1]) : 'ASC');
        } else {
            $this->orderBy(self::cyrus()->getKeyName());
        }

        // applying limit
        if (isset($arguments[0]['limit'])) {
            $start = '0';
            $offset = $arguments[0]['limit'];

            if (strpos($arguments[0]['limit'], ',') == true) {
                list($start, $offset) = explode(',', $arguments[0]['limit']);
            }
            $this->limit($start, $offset);
        }

        // applying pagination limit
        if (isset($arguments[0]['paginate']) || method_exists(self::cyrus(), 'pageLimit')) {
            $page = $offset = $start = "";
            $offset = self::cyrus()->perPage; //how many items to show per page
            $limit = !isset($arguments[0]['paginate']['limit']) ?
                self::cyrus()->pageLimit() :
                $arguments[0]['paginate']['limit'];

            $page = ($limit !== '') ? $limit : 0;

            if ($page) {
                $start = ($page - 1) * $offset; //first item to display on this page
            } else {
                $start = 0; //if no page var is given, set start to 0
            }

            $this->limit(intval($start), intval($offset));
        }

        // applying group by
        if (isset($arguments[0]['groupBy'])) {
            $this->groupBy(explode(',', $arguments[0]['groupBy']));
        }

        return $this->select('all')->findAll();
    }

    /**
     * We will get first row of table
     *
     * @return mixed
     */
    public function first()
    {
        return $this->findFirstOrLast();
    }

    private function findFirstOrLast($order = null)
    {
        $orderBy = (!is_null($order)) ? $order : 'ASC';

        $fetchObject = $this->select('all')
            ->orderBy(self::cyrus()->getKeyName(), $orderBy)
            ->limit(1)
            ->findAll();

        if ($fetchObject == null) {
            return self::cyrus()->returnEmptyObject();
        }

        return $fetchObject;
    }

    /*
    * Flush results after data retrieving process
    * It will unset all existing properties and close reader in order to make new selection process
    *
    */

    /**
     * Get last row of table
     *
     * @return mixed
     */
    public function last()
    {
        return $this->findFirstOrLast('DESC');
    }

    private function findBy($arguments)
    {
        $fetch = $this->select('*')->where($arguments[0], $arguments[1], $arguments[2])->findAll();

        if ($fetch == null) {
            return self::cyrus()->returnEmptyObject();
        }

        return $fetch;
    }

    /**
     * Check whether reader is closed or not.
     *
     * @return boolean whether the reader is closed or not.
     */
    private function isClosed()
    {
        return $this->closed;
    }
}
