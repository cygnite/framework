<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database;

/**
 * Database ActiveRecord.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
use PDO;
use Cygnite;
use Exception;
use PDOException;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Pagination;

//extends Connection
class Query
{
    //set your pdo statement here
    const DELETE = 'DELETE';

    //hold all your fields name which to select from table
    const LIMIT_STYLE_TOP_N = "top";
    const LIMIT_STYLE_LIMIT = "limit";
    public static $query;
    private static $debugQuery = array();
    private static $activeRecord;
    private static $cacheData = array();
    private static $dataSource = false;
    public $data = array();
    protected $_tableAlias;
    protected $joinSources = array();
    protected $pdo = array();
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
    // Join sources
    private $hasJoin = false;
    private $fromTable;
    /**
     * @access private
     * @var Array $where
     */
    private $where = array();
    /**
     * Bindings for where sql statement
     *
     * @access private
     * @var Array $bindings
     */
    private $bindings = array();

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
     * Get ActiveRecord instance
     *
     * @return null
     */
    public static function getActiveRecord()
    {
        return is_object(self::$activeRecord) ? self::$activeRecord : null;
    }

    public static function make(\Closure $callback, $activeRecord = null)
    {
        return $callback(new Query($activeRecord));
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
        $this->pdo[$connection] = Connection::getConnection($connection);
    }

    /**
     * Get Database Connection Object based on database name
     * provided into model class
     *
     * @return null|object
     */
    public function getDatabaseConnection()
    {
        $connection = self::getActiveRecord()->getDatabase();
        $this->setDatabaseConnection($connection);

        return is_object($this->pdo[$connection]) ? $this->pdo[$connection] : null;
    }

    /**
     * @param array $arguments
     * @return mixed
     * @throws \RuntimeException
     */
    public function insert($arguments = array())
    {
        $sql = $ar = null;
        $ar = self::getActiveRecord();
        $this->triggerEvent('beforeCreate');
        $sql = $this->getInsertQuery(strtoupper(__FUNCTION__));

        try {
            $statement = $this->getDatabaseConnection()->prepare($sql);
            // we will bind all parameters into the statement
            foreach ($arguments as $key => $val) {
                $statement->bindParam(":$key", $val);
            }

            if ($bool = $statement->execute()) {
                $ar->{$ar->getPrimaryKey()} = (int)$this->getDatabaseConnection()->lastInsertId();
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
        $ar = self::getActiveRecord();
        $this->triggerEvent('beforeUpdate');

        if (is_array($args) && !empty($args)) {
            $x = array_keys($args);
            $column = $x[0];
            $value = $args[$x[0]];
        } else {
            $column = $ar->getPrimaryKey();
            $value = $args;
        }

        $sql = $this->getUpdateQuery($column, strtoupper(__FUNCTION__));
        try {
            $statement = $this->getDatabaseConnection()->prepare($sql);
            //Bind all values to query statement
            foreach (array_merge($ar->attributes, array($column => $value)) as $key => $val) {
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
    public function trash($where, $multiple = false)
    {
        $whr = array();
        $statement = $affectedRows = null;
        $ar = self::getActiveRecord();
        $this->triggerEvent('beforeDelete');

        if (is_array($where) && $multiple == false) {
            $whr = array_keys($where);
            $this->where($this->quoteIdentifier($whr[0]), '=', $where[$whr[0]]);
        } else {
            if (is_array($where) && $multiple == true) {
                $this->whereIn($this->quoteIdentifier($ar->getPrimaryKey()), 'IN', implode(',', $where));
            }
        }

        if (is_string($where) || is_int($where)) {
            $this->where($this->quoteIdentifier($ar->getPrimaryKey()), '=', $where);
        }

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
     * Add a simple JOIN source to the query
     */
    public function join($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("", $table, $constraint, $tableAlias);
    }

    /**
     * Add an INNER JOIN souce to the query
     */
    public function leftJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("LEFT", $table, $constraint, $tableAlias);
    }

    /**
     * Add an INNER JOIN souce to the query
     */
    public function innerJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("INNER", $table, $constraint, $tableAlias);
    }

    /*
    * limit function to limit the database query
    * @access   public
    * @param    int
    * @return   object
    */

    /**
     * Add a LEFT OUTER JOIN souce to the query
     */
    public function leftOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("LEFT OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Add an RIGHT OUTER JOIN souce to the query
     */
    public function rightOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("RIGHT OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Add an FULL OUTER JOIN souce to the query
     */
    public function fullOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("FULL OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Add a RAW JOIN source to the query
     */
    public function rawJoin($table, $constraint, $tableAlias, $parameters = array())
    {
        // Add table alias if present
        if (!is_null($tableAlias)) {
            $tableAlias = $this->quoteIdentifier($tableAlias);
            $table .= " {$tableAlias}";
        }

        $this->_values = array_merge($this->_values, $parameters);

        // Build the constraint
        if (is_array($constraint)) {
            list($firstColumn, $operator, $secondColumn) = $constraint;
            $firstColumn = $this->quoteIdentifier($firstColumn);
            $secondColumn = $this->quoteIdentifier($secondColumn);
            $constraint = "{$firstColumn} {$operator} {$secondColumn}";
        }

        $this->joinSources[] = "{$table} ON {$constraint}";

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
        $data = array();
        $ar = self::getActiveRecord();
        $this->triggerEvent('beforeSelect');

        $this->buildQuery();
        try {
            $statement = $this->getDatabaseConnection()->prepare($this->sqlQuery);
            $this->sqlQuery = null;
            $this->setDbStatement($ar->getDatabase(), $statement);
            $this->bindParam($statement);
            $statement->execute();
            $data = $this->fetchAs($statement, $fetchMode);
            $this->triggerEvent('afterSelect');

            if ($statement->rowCount() > 0) {
                return new Collection($data);
            } else {
                return null;
            }
        } catch (PDOException $ex) {
            throw new \Exception("Database exceptions: Invalid query x" . $ex->getMessage());
        }
    }

    public function fetchAs($statement, $fetchMode = null)
    {
        $data = array();

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
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, '\\' . __NAMESPACE__ . '\\DataSource');
                break;
            default:
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, '\\' . self::getActiveRecord()->modelClassNs);
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
        $statement = $this->getDbStatement(self::getActiveRecord()->getDatabase());

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
        $condition = array();
        $condition = explode($type, str_replace('findBy', '', $method));

        if (count($condition) == count($arguments[0])) {

            foreach ($condition as $key => $value) {
                $field = Inflector::instance()->tabilize($value);
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
        $results = array();
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
        $ar = static::getActiveRecord();
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
    public function find($method, $options = array())
    {
        if (isset($options['primaryKey']) && $method == __FUNCTION__) {

            return $this->select('all')
                ->where(self::getActiveRecord()->getPrimaryKey(), '=', array_shift($options['args']))
                ->orderBy(self::getActiveRecord()->getPrimaryKey(), 'DESC')
                ->findAll();
        }

        return $this->{$method}($options);
    }

    public function debugLastQuery()
    {
        return end(static::$debugQuery);
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
        $statement = $this->getDbStatement(self::getActiveRecord()->getDatabase());

        $statement->closeCursor();
        $this->pdo = null;
        $this->closed = true;
    }

    /**
     * Query Internal method to add a JOIN source to the query.
     *
     * The join_operator should be one of INNER, LEFT OUTER, CROSS etc - this
     * will be prepended to JOIN.
     *
     * firstColumn, operator, secondColumn
     *
     * Example: array('user.id', '=', 'profile.user_id')
     *
     * will compile to
     *
     * ON `user`.`id` = `profile`.`user_id`
     *
     * The final (optional) argument specifies an alias for the joined table.
     */
    protected function addJoinSource($joinOperator, $table, $constraint, $tableAlias = null)
    {
        $joinOperator = trim("{$joinOperator} JOIN");
        $table = Inflector::instance()->tabilize($this->quoteIdentifier(lcfirst($table)));

        // Add table alias if exists
        if (!is_null($tableAlias)) {
            $table .= " {$tableAlias}";
        }

        // Build the constraint
        if (is_array($constraint)) {
            list($firstColumn, $operator, $secondColumn) = $constraint;
            $constraint = "{$firstColumn} {$operator} {$secondColumn}";
        }

        //$table = Inflector::instance()->tabilize(lcfirst($table));
        $this->hasJoin = true;
        $this->joinSources[] = "{$joinOperator} {$table} ON {$constraint}";

        return $this;
    }

    /**
     * Quote a string that is used as an identifier
     * (table names, column names etc) or an array containing
     * multiple identifiers. This method can also deal with
     * dot-separated identifiers eg table.column
     */
    protected function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            $result = array_map(array($this, 'quoteOneIdentifier'), $identifier);

            return join(', ', $result);
        } else {
            return Inflector::instance()->tabilize($this->quoteOneIdentifier(lcfirst($identifier)));
        }
    }

    /**
     * Quote a string that is used as an identifier
     * (table names, column names etc). This method can
     * also deal with dot-separated identifiers eg table.column
     */
    protected function quoteOneIdentifier($identifier)
    {
        $parts = explode('.', $identifier);
        $parts = array_map(array($this, 'quoteIdentifierSection'), $parts);

        return join('.', $parts);
    }

    /**
     * This method performs the actual quoting of a single
     * part of an identifier, using the identifier quote
     * character specified in the config (or autodetected).
     */
    protected function quoteIdentifierSection($part)
    {
        if ($part === '*') {
            return $part;
        }

        $quoteCharacter = '`';
        // double up any identifier quotes to escape them
        return $quoteCharacter .
        str_replace(
            $quoteCharacter,
            $quoteCharacter . $quoteCharacter,
            $part
        ) . $quoteCharacter;
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
        $instance = static::getActiveRecord();

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
     * @internal param Array $attributes
     * @return string
     */
    private function getInsertQuery($function)
    {
        $ar = static::getActiveRecord();
        $keys = array_keys($ar->attributes);

        return $function . " INTO `" . $ar->getDatabase() . "`.`" . $ar->getTableName() .
        "` (" . implode(",", $keys) . ")" .
        " VALUES(:" . implode(",:", $keys) . ")";
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
        $ar = static::getActiveRecord();
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
        if (method_exists(self::getActiveRecord(), 'exceptColumns')) {
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
        $ar = self::getActiveRecord();
        // we will get the table schema
        $select = Schema::instance(
            $this,
            function ($table) use ($ar) {
                $table->database = $ar->getDatabase();
                $table->tableName = $ar->getTableName();

                return $table->getColumns();
            }
        );

        $columns = $this->query($select->schema)->getAll();

        // Get all column name which need to remove from the result set
        $exceptColumns = $ar->exceptColumns();
        $columnArray = array();
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
                self::getActiveRecord()->getTableName()
            ) . ' ' . $this->_tableAlias . $this->getJoinSource() . $this->getWhere() .
            ' ' . $this->getGroupBy() . ' ' . $this->getOrderBy() . ' ' . $this->getLimit();

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
                self::getActiveRecord()->getTableName()
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
                self::getActiveRecord()->getTableName()
            ) . ' ' . $this->_selectColumns : $this->_selectColumns;
    }

    /**
     * @return string
     */
    private function getJoinSource()
    {
        $joinSource = '';
        if ($this->hasJoin == true) {
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
                    self::getActiveRecord()->getTableName()
                ) . '.*';
        } else {

            if (strpos($column, 'AS') !== false || strpos($column, 'as') !== false) {
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
    private function all($arguments)
    {
        if (isset($arguments[0]['orderBy'])) {
            $exp = array();
            $exp = explode(' ', $arguments[0]['orderBy']);
            $this->orderBy($this->quoteIdentifier($exp[0]), (isset($exp[1])) ? strtoupper($exp[1]) : 'ASC');
        } else {
            $this->orderBy(self::getActiveRecord()->getPrimaryKey());
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
        if (isset($arguments[0]['paginate']) || method_exists(self::getActiveRecord(), 'pageLimit')) {

            $page = $offset = $start = "";
            $offset = self::getActiveRecord()->perPage; //how many items to show per page
            $limit = !isset($arguments[0]['paginate']['limit']) ?
                self::getActiveRecord()->pageLimit() :
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
    private function first()
    {
        return $this->findFirstOrLast();
    }

    private function findFirstOrLast($order = null)
    {
        $orderBy = (!is_null($order)) ? $order : 'ASC';

        $fetchObject = $this->select('all')
            ->orderBy(self::getActiveRecord()->getPrimaryKey(), $orderBy)
            ->limit(1)
            ->findAll();

        if ($fetchObject == null) {
            return self::getActiveRecord()->returnEmptyObject();
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
    private function last()
    {
        return $this->findFirstOrLast('DESC');
    }

    private function findBy($arguments)
    {
        $fetch = $this->select('*')->where($arguments[0], $arguments[1], $arguments[2])->findAll();

        if ($fetch == null) {
            return self::getActiveRecord()->returnEmptyObject();
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
