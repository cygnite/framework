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

use Cygnite;
use Cygnite\Common\Pagination;
use Cygnite\Database\ConnectionManagerTrait;
use Cygnite\Database\Cyrus\ActiveRecord;
use Cygnite\Database\Table\Schema;
use Cygnite\Foundation\Collection;
use Cygnite\Helpers\Inflector;
use Exception;
use PDO;
use PDOException;

/**
 * Class Builder.
 */
class Builder extends Joins implements QueryBuilderInterface
{
    use ConnectionManagerTrait;

    const DELETE = 'DELETE';

    //hold all your fields name which to select from table
    const LIMIT_STYLE_TOP_N = 'top';
    const LIMIT_STYLE_LIMIT = 'limit';

    // Holds query builder instance
    public static $query;

    public static $debugQuery = [];

    // Holds ActiveRecord instance
    protected static $activeRecord;

    protected static $cacheData = [];

    public static $dataSource = false;

    public $data = [];

    // Table Alias String
    protected $tableAlias;

    // Holds PDO instance
    protected $pdo = [];
    protected $statement;

    // set query builder query into property
    protected $selectColumns;

    // Limit clause style
    protected $limitValue;
    protected $offsetValue;

    //Hold your pdo connection object
    protected $columnName;
    protected $orderType;

    // Set group by column
    protected $groupBy;

    // Distinct column clause
    protected $distinct;

    // Holds Sql Query
    protected $sqlQuery;
    protected $closed;

    // Table name
    protected $fromTable;
    /**
     * @var array
     */
    protected $where = [];
    /**
     * Bindings for where sql statement.
     *
     * @var array
     */
    protected $bindings = [];

    // Holds Queries executed
    public static $queries = [];

    /**
     * The current query HAVING value bindings.
     *
     * @var array
     */
    protected $havingConditions = [];

    protected $havingType;

    /**
     * Constructor of Query Builder.
     *
     * @param ActiveRecord $ar
     */
    public function __construct($ar = null)
    {
        if ($ar instanceof ActiveRecord) {
            $this->setActiveRecord($ar);
        }

        self::$query = $this;
    }

    /**
     * Set ActiveRecord instance.
     *
     * @param $instance
     */
    public function setActiveRecord($instance)
    {
        self::$activeRecord = $instance;

        return $this;
    }

    /**
     * Get Cyrus ActiveRecord instance.
     *
     * @return null
     */
    public static function cyrus()
    {
        return is_object(self::$activeRecord) ? self::$activeRecord : null;
    }

    /**
     * We will set Database Connection object.
     *
     * @param $connection
     */
    public function setDatabaseConnection($connection)
    {
        $this->pdo[$connection] = $this->getConnection($connection);
    }

    /**
     * Get Database Connection Object based on database name
     * provided into model class.
     *
     * @return null|object
     */
    public function resolveConnection()
    {
        $connection = self::cyrus()->getDatabase();

        $this->setDatabaseConnection($connection);

        return is_object($this->pdo[$connection]) ? $this->pdo[$connection] : null;
    }

    /**
     * Insert a record into database.
     *
     * @param array $arguments
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function insert($arguments = [])
    {
        $ar = null;
        $ar = self::cyrus();
        /*
         | Trigger Before create events if
         | defined by user into model class
         |
         */
        $this->triggerEvent('beforeCreate');

        // Build Sql Query and prepare it
        $sql = $this->getInsertQuery(strtoupper(__FUNCTION__), $ar, $arguments);

        try {
            $statement = $this->resolveConnection()->prepare($sql);
            static::$queries[] = $statement->queryString;

            // we will bind all parameters into the statement using execute method
            if ($return = $statement->execute($arguments)) {
                $ar->{$ar->getKeyName()} = (int) $this->resolveConnection()->lastInsertId();
                /*
                 | Trigger after create events if
                 | defined by user into model class
                 |
                 */
                $this->triggerEvent('afterCreate');

                return $return;
            }
        } catch (PDOException  $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    /**
     * @param $where
     *
     * @return string
     */
    public function buildWherePlaceholderName($where)
    {
        $whereStr = '';
        $i = 0;
        foreach ($where as $key => $value) {
            if ($i == 0) {
                $whereStr .= "$key = :$key";
            } else {
                $whereStr .= " AND $key = :$key";
            }

            $i++;
        }

        return ' WHERE '.ltrim($whereStr, ' AND ');
    }

    /**
     * @param $where
     *
     * @return array
     */
    protected function formatWhereToNamePlaceHolder($where)
    {
        $whereStr = '';
        $whereNew = [];
        foreach ($where as $key => $value) {
            $key = trim(str_replace(['AND', '?', '='], ['', '', ''], $key));
            $whereNew[$key] = $value;
            $whereStr .= "$key = :$key,";
        }

        return [$whereNew, rtrim($whereStr, ',')];
    }

    /**
     * Build where condition for Update query.
     *
     * @return array
     */
    private function buildWhereForUpdate()
    {
        $whereArray = array_combine($this->where, $this->bindings);
        list($whereNew, $condition) = $this->formatWhereToNamePlaceHolder($whereArray);

        return [$whereNew, $this->buildWherePlaceholderName($whereNew)];
    }

    /**
     * Update record with new values.
     *
     * @param $args
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function update($data, $where = [])
    {
        /*
         | Trigger Before Update events if
         | defined by user into model class
         |
         */
        $this->triggerEvent('beforeUpdate');

        if (!empty($where)) {
            $whereStr = $this->buildWherePlaceholderName($where);
        } else {
            list($where, $whereStr) = $this->buildWhereForUpdate();
        }

        $sql = $this->getUpdateQuery($data, strtoupper(__FUNCTION__)).$whereStr;

        try {
            $stmt = $this->resolveConnection()->prepare($sql);

            //Bind all values to query statement
            foreach ($data as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }

            foreach ($where as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            static::$queries[] = $stmt->queryString;
            $affectedRow = $stmt->execute();

            /*
             | Trigger after update events if defined
             | into model class
             |
             */
            $this->triggerEvent('afterUpdate');

            return $affectedRow;
        } catch (\PDOException  $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Trash method.
     *
     * Delete a row from the table
     *
     * <code>
     * $user = new User();
     * $user->trash(1);
     * $user->trash([1,4,6,33,54], true)
     * $user->trash(['id' => 23])
     * $user->where('name', '=', 'application')->trash();
     *
     * </code>
     *
     * @param array $where
     *                        $multiple false
     * @param bool  $multiple
     *
     * @throws \Exception
     *
     * @internal  param \Cygnite\Database\the $string table to retrieve the results from
     *
     * @return object
     */
    public function trash($where = null, $multiple = false)
    {
        $whr = [];
        $ar = self::cyrus();
        /*
         | Trigger Before Delete events if
         | defined by user into model class
         |
         */
        $this->triggerEvent('beforeDelete');

        // Bind where conditions
        $this->bindWhereClause($where, $multiple, $ar);

        $sql = self::DELETE.
            ' FROM '.$this->quoteIdentifier($ar->getDatabase()).'.'.$this->quoteIdentifier($ar->getTableName())
            .$this->getWhere();

        try {
            /*
             | Get the Connection, prepare and execute sql query
             | return affected rows
             */
            $stmt = $this->resolveConnection()->prepare($sql);
            $this->bindParam($stmt); //bind parameters
            static::$queries[] = $stmt->queryString;
            $affectedRow = $stmt->execute();

            /*
             | Trigger after delete model events if defined
             */
            $this->triggerEvent('afterDelete');

            return $affectedRow;
        } catch (\PDOException  $ex) {
            throw new DatabaseException($ex->getMessage());
        }
    }

    /**
     * <code>
     * $user = new User();
     * $user->trash(1);
     * $user->trash([1,4,6,33,54], true)
     * $user->trash(['id' => 23])
     * $user->where('name', '=', 'application')->trash();.
     *
     * </code>
     *
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

    public function truncate()
    {
    }

    /**
     * Find Function to selecting Table columns.
     *
     * Generates the SELECT portion of the query
     *
     * @param   $column
     *
     * @throws \Exception
     *
     * @return object
     */
    public function select($column)
    {
        //select columns
        if (is_string($column) && !is_null($column)) {
            return $this->_select($column);
        }
    }

    /**
     * Internally build select Columns.
     *
     * @param $column
     *
     * @return $this
     */
    private function _select($column)
    {
        if ($column === 'all' || $column == '*') {
            $this->selectColumns = $this->quoteIdentifier(
                    self::cyrus()->getTableName()
                ).'.*';
        } else {
            if (string_has($column, 'as') || string_has($column, 'AS')) {
                return $this->selectExpr($column);
            }

            $this->selectColumns = (string) str_replace(' ', '', $this->quoteIdentifier(explode(',', $column)));
        }

        return $this;
    }

    /**
     * Add an unquoted expression to the list of columns returned
     * by the SELECT query. The second optional argument is
     * the alias to return the column as.
     *
     * @param $expr
     *
     * @return $this
     */
    public function selectExpr($expr)
    {
        $this->selectColumns = (string) $expr;

        return $this;
    }

    /**
     * Adding an element in the where array with the value
     * to the bindings.
     *
     * @param string $key
     * @param string $operator
     * @param string $value
     *
     * @return void
     */
    public function where($key, $operator, $value)
    {
        if (string_has($operator, 'IN') || string_has($operator, 'in')) {
            return $this->whereIn($key, $value);
        }

        $this->where[] = 'AND '.$key.' '.$operator.' '.'?';
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Adding an element in the where array with the value
     * to the bindings.
     *
     * @param $key
     * @param $value
     *
     * @return $this|mixed
     */
    public function whereIn($key, $value)
    {
        $exp = explode(',', $value);
        $this->where[] = 'AND '.$key.' IN ('.$this->createPlaceHolder($exp).') ';

        foreach ($exp as $key => $val) {
            $this->bindings[$key] = $val;
        }

        return $this;
    }

    /**
     * Adding an element in the where array with the value
     * to the bindings.
     *
     * @param string $key
     * @param string $operator
     * @param string $value
     *
     * @return void
     */
    public function orWhere($key, $operator, $value)
    {
        $this->where[] = 'OR '.$key.' '.$operator.' '.'?';
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Where conditions with "or".
     *
     * @param        $key
     * @param        $value
     * @param string $operator
     *
     * @return $this
     */
    public function orWhereIn($key, $value, $operator = 'IN')
    {
        $exp = explode(',', $value);
        $this->where[] = 'OR OR'.$key.' '.$operator.' ('.$this->createPlaceHolder($exp).') ';

        foreach ($exp as $key => $val) {
            $this->bindings[$key] = $val;
        }

        return $this;
    }

    /**
     * Add having clause to the query.
     *
     * @param        $column
     * @param string $operator
     * @param null   $value
     *
     * @return $this
     */
    public function having($column, $operator = '=', $value = null)
    {
        $this->havingType = 'AND';

        return $this->addCondition('having', $column, $operator, $value);
    }

    /**
     * Add having clause to the query prefixing OR keyword.
     *
     * @param        $column
     * @param string $operator
     * @param null   $value
     *
     * @return $this|Builder
     */
    public function orHaving($column, $operator = '=', $value = null)
    {
        $this->havingType = 'OR';

        return $this->addCondition('having', $column, $operator, $value);
    }

    /**
     * Method to compile a simple column separated value for HAVING clause.
     * If column passed as array, we will add condition for each column.
     *
     * @param $type
     * @param $column
     * @param $separator
     * @param $value
     *
     * @return $this
     */
    protected function addCondition($type, $column, $separator, $value)
    {
        $multiple = is_array($column) ? $column : [$column => $value];

        foreach ($multiple as $key => $val) {

            // Add the table name in case of ambiguous columns
            if (count($this->joinSources) > 0 && string_has($key, '.')) {
                $table = (!is_null($this->tableAlias)) ? $this->tableAlias : $this->formTable;

                $key = "{$table}.{$key}";
            }

            $key = $this->quoteIdentifier($key);
            $this->bindCondition($type, "{$key} {$separator} ?", $val);
        }

        return $this;
    }

    /**
     * Add a raw HAVING clause to the query. You can also bing values by passing
     * second parameter as array. Make sure you clause should contain question mark
     * placeholder.
     *
     * @param       $clause
     * @param array $values
     *
     * @return $this
     */
    public function havingRaw($clause, $values = [])
    {
        return $this->bindCondition('having', $clause, $values);
    }

    /**
     * Build Having Conditions for the query.
     *
     * @param       $type
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    protected function bindCondition($type, $key, $values = [])
    {
        $conditionHolder = "{$type}Conditions";
        if (!is_array($values)) {
            $values = [$values];
        }

        array_push($this->$conditionHolder, [$key, $values]);

        return $this;
    }

    /**
     * Build the HAVING clause(s).
     *
     * @return string
     */
    protected function buildHavingConditions()
    {
        $join = ($this->havingType == 'AND') ? 'AND' : 'OR';
        $this->havingType = null;

        return $this->makeConditions('having', $join);
    }

    /**
     * Build a HAVING clause conditions.
     *
     * @param string $type
     * @param string $join
     *
     * @return string
     */
    protected function makeConditions($type, $join = 'AND')
    {
        $conditionHolder = "{$type}Conditions";

        // If there are no clauses, return empty string
        if (count($this->$conditionHolder) === 0) {
            return '';
        }

        $conditions = [];

        /*
         * Bind all values to property to execute query
         */
        foreach ($this->$conditionHolder as $condition) {
            $conditions[] = $condition[0];
            $this->bindings = array_merge($this->bindings, $condition[1]);
        }

        return strtoupper($type).' '.implode(" $join ", $conditions);
    }

    /**
     * Get the distinct value of the column.
     *
     * @param $column
     *
     * @return $this
     */
    public function distinct($column)
    {
        $this->distinct = (string) (strtolower(__FUNCTION__).($column));

        return $this;
    }

    /**
     * Limit the record and fetch from database.
     *
     * @param type   $limit
     * @param string $offset
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function limit($limit, $offset = '')
    {
        if (is_null($limit)) {
            throw new \Exception('Empty parameter given to limit clause ');
        }

        if (empty($offset) && !empty($limit)) {
            $this->limitValue = 0;
            $this->offsetValue = intval($limit);
        } else {
            $this->limitValue = intval($limit);
            $this->offsetValue = intval($offset);
        }

        return $this;
    }

    /**
     * This function to make order for selected query.
     *
     * @param        $column
     * @param string $orderType
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function orderBy($column, $orderType = 'ASC')
    {
        if (empty($column) || is_null($column)) {
            throw new \Exception('Empty parameter given to order by clause');
        }
        $this->orderType = $orderType;

        if (is_array($column)) {
            $this->columnName = $this->extractArrayAttributes($column);

            return $this;
        }

        if (is_null($this->columnName)) {
            $this->columnName = $column;
        }

        return $this;
    }

    /**
     * Group By function to group columns based on aggregate functions.
     *
     * @param $column
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function groupBy($column)
    {
        if (is_null($column)) {
            throw new \InvalidArgumentException('Cannot pass null argument to '.__METHOD__);
        }

        if (is_array($column)) {
            $this->groupBy = $this->extractArrayAttributes($column);

            return $this;
        }

        $this->groupBy = $this->quoteIdentifier($column);

        return $this;
    }

    /**
     * Add an alias for the main table to be used in SELECT queries.
     *
     * @param $alias
     *
     * @return $this
     */
    public function tableAlias($alias)
    {
        $this->tableAlias = $alias;

        return $this;
    }

    /**
     * Build and Find all the matching records from database.
     * By default its returns class with properties values
     * You can simply pass fetchMode into findAll to get various
     * format output.
     *
     * @param string $type
     *
     * @throws \Exception
     *
     * @internal param string $type
     *
     * @return array or object
     */
    public function findAll($type = '')
    {
        $data = [];
        $ar = self::cyrus();
        /*
         | Trigger before select events defined into
         | model class
         */
        $this->triggerEvent('beforeSelect');

        $this->buildQuery(); // Build Sql Query

        try {
            $stmt = $this->resolveConnection()->prepare($this->sqlQuery);

            $this->sqlQuery = null;
            $this->setDbStatement($ar->getTableName(), $stmt);
            $this->bindParam($stmt);
            $stmt->execute();
            $data = $this->fetchAs($stmt, $type);
            /*
             | Trigger after select events defined into model class
             */
            $this->triggerEvent('afterSelect');

            if ($stmt->rowCount() > 0) {
                return new Collection($data);
            } else {
                return new Collection([]);
            }
        } catch (PDOException $ex) {
            throw new \Exception('Database exceptions: Invalid query x'.$ex->getMessage());
        }
    }

    /**
     * This is alias method of findAll().
     *
     * @param string $type
     *
     * @return mixed
     */
    public function findMany($type = '')
    {
        return $this->findAll($type);
    }

    /**
     * This method is alias of findAll, We will get only the
     * zeroth row from the collection object.
     *
     * @return object|null
     */
    public function findOne($type = '')
    {
        $rows = $this->findAll($type)->asArray();

        return isset($rows[0]) ? $rows[0] : null;
    }

    /**
     * Allows to fetch records as type specified.
     *
     * @param      $stmt
     * @param null $type
     *
     * @return string
     */
    public function fetchAs($stmt, $type = null)
    {
        $data = [];

        if ((is_null($type) || $type == '') && static::$dataSource) {
            $type = 'class';
            static::$dataSource = false;
        }

        switch (strtolower($type)) {
            case 'group':
                $data = $stmt->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
                break;
            case 'both':
                $data = $stmt->fetchAll(\PDO::FETCH_BOTH);
                break;
            case 'object':
                $data = $stmt->fetchAll(\PDO::FETCH_OBJ);
                break;
            case 'assoc':
                $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                break;
            case 'column':
                $data = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'class':
                $data = $stmt->fetchAll(\PDO::FETCH_CLASS, '\\Cygnite\\Database\\ResultSet');
                break;
            default:
                $data = $stmt->fetchAll(\PDO::FETCH_CLASS, '\\'.self::cyrus()->getModelClassNs());
                break;
        }

        return $data;
    }

    public function getGroupBy()
    {
        return (isset($this->groupBy) && !is_null($this->groupBy)) ?
            'GROUP BY '.$this->groupBy : '';
    }

    public function getOrderBy()
    {
        return (isset($this->columnName) && isset($this->orderType)) ?
            ' ORDER BY '.$this->columnName.'  '.$this->orderType : '';
    }

    public function getLimit()
    {
        return (isset($this->limitValue) && isset($this->offsetValue)) ?
            ' LIMIT '.$this->limitValue.','.$this->offsetValue.' ' : '';
    }

    /**
     * Get row count.
     *
     * @return mixed
     */
    public function rowCount()
    {
        $stmt = $this->getDbStatement(self::cyrus()->getDatabase());

        return $stmt->rowCount();
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function getDbStatement($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : null;
    }

    /**
     * Build raw queries.
     *
     * @param string $sql
     * @param array  $attributes
     *
     * @throws \Exception|\PDOException
     *
     * @return object pointer $this
     */
    public function query($sql, $attributes = [])
    {
        try {
            $this->statement = $this->resolveConnection()->prepare($sql);

            if (!empty($attributes)) {
                $this->statement->execute($attributes);
            } else {
                $this->statement->execute();
            }
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Execute Prepared Query.
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->statement->execute();
    }

    /**
     * @return mixed
     */
    public function getOne()
    {
        return $this->first();
    }

    /**
     * Get all rows of table as Collection.
     *
     * @return array|Collection
     */
    public function getAll()
    {
        return new Collection($this->fetchAs($this->statement));
    }

    /**
     * @param string     $req       : the query on which link the values
     * @param array      $array     : associative array containing the values ??to bind
     * @param array|bool $typeArray : associative array with the desired value for its
     *                              corresponding key in $array
     *
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
     *
     * @throws \Exception
     *
     * @return object
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
     * Find result using raw sql query.
     *
     * @param $arguments
     *
     * @return Collection
     */
    public function findBySql($arguments)
    {
        $results = [];
        $stmt = $this->resolveConnection()->prepare(trim($arguments[0]));
        $stmt->execute();
        $results = $this->fetchAs($stmt);

        return new Collection($results);
    }

    /**
     * Select from table. Alias method of table.
     *
     * @param $table
     *
     * @return $this
     */
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
     * Set table to run fluent query without model class.
     *
     * @param $table
     *
     * @return $this
     */
    public function table($table)
    {
        $ar = static::cyrus();
        $ar->setTableName($table);
        static::$dataSource = true;

        return $this;
    }

    /**
     * Internally call finder methods.
     *
     * @param       $method
     * @param array $params
     *
     * @return mixed
     */
    public function callFinder($method, array $params)
    {
        return $this->find($method, $params);
    }

    /**
     * Find a single row.
     *
     * @param       $method
     * @param array $options
     *
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
     * We will return last executed query.
     *
     * @return string
     */
    public function lastQuery()
    {
        return end(static::$queries);
    }

    /**
     *  Flush Connection object.
     */
    public function flush()
    {
        if ($this->isClosed() == false) {
            $this->close();
            $this->closed = false;
        }
    }

    /**
     * Closes the reader.
     *
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        $stmt = null;
        $stmt = $this->getDbStatement(self::cyrus()->getDatabase());

        $stmt->closeCursor();
        $this->pdo = null;
        $this->closed = true;
    }

    /**
     * We will trigger event.
     *
     * @param $event
     */
    protected function triggerEvent($event)
    {
        $instance = static::cyrus();

        if (method_exists($instance, $event) &&
            in_array($event, $instance->getModelEvents())
        ) {
            $instance->{$event}($instance);
        }
    }

    /**
     * Get insert sql statement.
     *
     * @param $function
     * @param $ar
     * @param $arguments
     *
     * @return string
     */
    private function getInsertQuery($function, $ar, $arguments)
    {
        $keys = array_keys($arguments);

        return $function.' INTO `'.$ar->getDatabase().'`.`'.$ar->getTableName().
        '` ('.implode(', ', $keys).')'.
        ' VALUES(:'.implode(', :', $keys).')';
    }

    /**
     * Get update sql statement.
     *
     * @param $data
     * @param $function
     *
     * @return string
     */
    private function getUpdateQuery($data, $function)
    {
        $ar = static::cyrus();
        $data = (empty($data)) ? $ar->getAttributes() : $data;
        /*
         | we will unset primary key from given array
         | This will avoid sql error (SQLSTATE[HY000]: General error: 2031)
         */
        unset($data[$ar->getKeyName()]);

        $sql = '';
        foreach ($data as $key => $value) {
            $sql .= "$key = :$key,";
        }

        $sql = rtrim($sql, ',');

        return $function.' `'.$ar->getDatabase().'`.`'.$ar->getTableName().'` SET '.' '.$sql;
    }

    /**
     * We will bind Parameters to execute queries.
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

    /**
     * @param $sql
     */
    private function setDebug($sql)
    {
        static::$debugQuery[] = $sql;
    }

    /**
     * Create placeholders for arguments.
     *
     * @param $arguments
     *
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
     *
     * @return string
     */
    private function extractArrayAttributes($columns)
    {
        $i = 0;
        $str = '';
        $count = count($columns);
        while ($i < $count) { //Create conditions with and if value is passed as array
            $str .= $this->quoteIdentifier($columns[$i]);
            $str .= ($i < $count - 1) ? ',' : '';
            $i++;
        }

        return $str;
    }

    /**
     * Build query internally.
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
     * and assign it to selectColumns to find the results.
     */
    private function prepareExceptColumns()
    {
        $ar = self::cyrus();

        if (!method_exists($ar, 'skip')) {
            return;
        }

        // we will get the table schema
        $select = Schema::make($this, function ($table) use ($ar) {
            $table->database = $ar->getDatabase();
            $table->tableName = $ar->getTableName();

            return $table->getColumns();
        });

        $columns = $this->query($select->schema)->getAll();

        // Get all column name which need to remove from the result set
        $exceptColumns = $ar->skip();
        $columnArray = [];

        foreach ($columns as $key => $value) {
            if (!in_array($value->COLUMN_NAME, $exceptColumns)) {
                $columnArray[] = $value->COLUMN_NAME;
            }
        }

        $this->selectColumns = (string) implode(',', $columnArray);
    }

    /**
     * @return $this
     */
    protected function buildSqlQuery()
    {
        $this->sqlQuery =
            'SELECT '.$this->buildSelectedColumns().' FROM '.$this->quoteIdentifier(
                self::cyrus()->getTableName()
            ).' '.$this->tableAlias.$this->getJoinSource().$this->getWhere().
            ' '.$this->getGroupBy().' '.$this->buildHavingConditions().' '.$this->getOrderBy().' '.$this->getLimit();

        static::$queries[] = $this->sqlQuery;

        return $this;
    }

    /**
     * We can use this method to debug query which
     * executed last.
     *
     * @return string
     */
    private function buildOriginalQuery()
    {
        return
            'SELECT '.$this->buildSelectedColumns().' FROM '.$this->quoteIdentifier(
                self::cyrus()->getTableName()
            ).' '.$this->getWhere().
            ' '.$this->getGroupBy().' '.$this->buildHavingConditions().' '.$this->getOrderBy().' '.$this->getLimit();
    }

    /**
     * @return string
     */
    private function buildSelectedColumns()
    {
        if (is_null($this->selectColumns)) {
            $this->selectColumns = '*';
        }

        return ($this->selectColumns == '*') ?
            $this->quoteIdentifier(
                self::cyrus()->getTableName()
            ).' .'.$this->selectColumns : $this->selectColumns;
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
     * Get where statement.
     *
     * @return string
     */
    private function getWhere()
    {
        // If where is empty return empty string
        if (empty($this->where)) {
            return '';
        }

        // Implode where pecices then remove the first AND or OR
        return ' WHERE '.ltrim(implode(' ', $this->where), 'ANDOR');
    }

    /**
     * @param $key
     * @param $value
     */
    private function setDbStatement($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Find all values from the database table.
     *
     * @param $arguments
     *
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
            $page = $offset = $start = '';
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
     * We will get first row of table.
     *
     * @return mixed
     */
    public function first()
    {
        return $this->findFirstOrLast();
    }

    /**
     * Method to find first oof last row of the table.
     *
     * @param null $order
     *
     * @return mixed
     */
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

    /**
     * Get last row of table.
     *
     * @return mixed
     */
    public function last()
    {
        return $this->findFirstOrLast('DESC');
    }

    /**
     * @param $arguments
     *
     * @return mixed
     */
    private function findBy($arguments)
    {
        $results = $this->select('*')->where($arguments[0], $arguments[1], $arguments[2])->findAll();

        if (is_null($results)) {
            return self::cyrus()->returnEmptyObject();
        }

        return $results;
    }

    /**
     * Check whether reader is closed or not.
     *
     * @return bool whether the reader is closed or not.
     */
    private function isClosed()
    {
        return $this->closed;
    }

    public static function make(\Closure $callback, $activeRecord = null)
    {
        return $callback(new static($activeRecord));
    }

    public static function _callMethod(\Closure $callback, $instance = null)
    {
        return $callback(self::$query, $instance);
    }
}
