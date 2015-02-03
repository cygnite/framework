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
    public $data = array();
    protected $_tableAlias;
    protected $joinSources = array();
    private $_statement;
    private $_selectColumns;

    // set query builder query into property
    private $_fromWhere;
    private $_columnWhere;
    private $_whereType;
    private $_limitValue;
    private $_offsetValue;
    private $_columnName;

    //Hold your pdo connection object
    private $_orderType;
    private $_groupBy;

    // Limit clause style
    private $distinct;
    private $sqlQuery;
    private $closed;

    // Join sources
    private $pdo;
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

    /*
     * Save data into table
     * @access private
     * @param $arguments empty array
     * @return true
     *
     */

    private function setActiveRecord($instance)
    {
        self::$activeRecord = $instance;
    }

    /*
    * Update user data into table by key
    * @access public
    * @param $args update by table fields
    * @return boolean
    *
    */

    public static function make(\Closure $callback, $activeRecord = null)
    {
        return $callback(new Query($activeRecord));
    }

    public static function _callMethod(\Closure $callback, $instance = null)
    {
        return $callback(self::$query, $instance);
    }

    public function insert($arguments = array())
    {
        $fields = $values = array();
        $query = $debugQuery = "";

        if (method_exists(self::getActiveRecordInstance(), 'beforeCreate') &&
            in_array('beforeCreate', self::getActiveRecordInstance()->getModelEvents())
        ) {
            self::getActiveRecordInstance()->{'beforeCreate'}(self::getActiveRecordInstance());
        }

        $insertMethod = null;
        $insertMethod = strtoupper(__FUNCTION__) . ' INTO';

        foreach (array_keys($arguments) as $key) {

            $fields[] = "`$key`";
            $values[] = "'" . $arguments[$key] . "'";
            $placeholder[] = substr(str_repeat('?,', count($key)), 0, -1);
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);
        $placeHolders = implode(',', $placeholder);

        $query = $insertMethod . " `" . self::getActiveRecordInstance()->getDatabase(
            ) . "`.`" . self::getActiveRecordInstance()->getTableName() . "`
           ($fields) VALUES" . " ($placeHolders)" . ";";

        $debugQuery = $insertMethod . " `" . self::getActiveRecordInstance()->getDatabase(
            ) . "`.`" . self::getActiveRecordInstance()->getTableName() . "`
           ($fields) VALUES" . " ($values)" . ";";

        try {
            $statement = $this->getDatabaseConnection()->prepare($query);

            if (true == $statement->execute(array_values(self::getActiveRecordInstance()->attributes))) {
                self::getActiveRecordInstance()->{self::getActiveRecordInstance()->getPrimaryKey(
                )} = (int)$this->getDatabaseConnection()->lastInsertId();

                if (method_exists(self::getActiveRecordInstance()->attributes, 'afterCreate') &&
                    in_array('afterCreate', self::getActiveRecordInstance()->getModelEvents())
                ) {
                    self::getActiveRecordInstance()->{'afterCreate'}(self::getActiveRecordInstance());
                }

                return true;
            }
        } catch (PDOException  $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    public static function getActiveRecordInstance()
    {
        return is_object(self::$activeRecord) ? self::$activeRecord : null;
    }

    /**
     * Get Database Connection Object
     *
     * @return null|object
     */
    public function getDatabaseConnection()
    {
        $this->setDatabaseConnection(self::getActiveRecordInstance()->getDatabase());

        return is_object($this->pdo) ? $this->pdo : null;
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

    public function setDatabaseConnection($connection)
    {
        $this->pdo = Connection::getConnection($connection);
    }

    public function update($args)
    {
        $query = $debugQuery = $x = "";
        $updateBy = $updateValue = null;
        $ar = self::getActiveRecordInstance();

        if (method_exists($ar, 'beforeUpdate') &&
            in_array('beforeUpdate', $ar->getModelEvents())
        ) {
            $ar->{'beforeUpdate'}($ar);
        }

        if ((is_array($args) && !empty($args))) {
            $x = array_keys($args);
            $updateBy = $x[0];
            $updateValue = $args[$x[0]];
        } else {
            echo $updateBy = $ar->getPrimaryKey();
            $updateValue = $args;
        }

        $query .= strtoupper(__FUNCTION__) . " `" . $ar->getDatabase() . "`.`" . $ar->getTableName() . "` SET ";
        $debugQuery .= strtoupper(__FUNCTION__) . " `" . $ar->getDatabase() . "`.`" . $ar->getTableName() . "` SET ";
        $arrCount = count($ar->attributes);
        $i = 0;

        foreach ($ar->attributes as $key => $value) {

            $query .= " `" . $key . "` " . "=" . " '" . $value . "'" . " ";
            $debugQuery .= " `" . $key . "` " . "=" . " '" . $value . "'" . " ";
            $query .= ($i < $arrCount - 1) ? ',' : '';
            $debugQuery .= ($i < $arrCount - 1) ? ',' : '';

            $i++;
        }

        $query .= " WHERE " . $updateBy . " =  :column";
        $debugQuery .= " WHERE " . $updateBy . " = " . $updateValue;

        $this->setDebug($debugQuery);
        try {
            $statement = $this->getDatabaseConnection()->prepare($query);
            $statement->bindValue(':column', $updateValue);
            $statement->execute();

            if (method_exists($ar, 'afterUpdate') &&
                in_array('afterUpdate', $ar->getModelEvents())
            ) {
                $ar->{'afterUpdate'}($ar);
            }

            return $statement->rowCount();

        } catch (\PDOException  $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    private function setDebug($sql)
    {
        static::$debugQuery[] = $sql;
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
        $column = $value = $statement = null;
        $ar = self::getActiveRecordInstance();

        if (method_exists($ar, 'beforeDelete') &&
            in_array('beforeDelete', $ar->getModelEvents())
        ) {
            $ar->{'beforeDelete'}($ar);
        }

        if (is_array($where) && $multiple == false) {
            $whr = array_keys($where);
            $column = $whr[0];
            $value = $where[$whr[0]];
            $condition = "` WHERE `" . $column . "` = " . $value;
        }

        if (is_string($where) || is_int($where)) {
            $column = $ar->getPrimaryKey();
            $value = $where;
            $condition = "` WHERE `" . $column . "` = " . $value;
        }

        $values = array();

        if (is_array($where) && $multiple == true) {
            $condition = "` WHERE `" . $ar->getPrimaryKey() . "` IN (" . implode(',', $where) . ")";
            $output = array_map(
                function ($val) {
                    return '?';
                },
                $where
            );
            $debugQuery = "` WHERE `id` IN (" . implode(',', $output) . ")";
            $sqlQuery = self::DELETE . " FROM `" . $ar->getTableName() . $condition;
            $debugQuery = self::DELETE . " FROM `" . $ar->getTableName() . $debugQuery;
        } else {
            $sqlQuery =
                self::DELETE . " FROM `" . $ar->getTableName() . "` WHERE `" . $column . "` = :where";
            $debugQuery =
                self::DELETE . " FROM `" . $ar->getTableName() . "` WHERE `" . $column . "` = " . $value;
        }

        /** @var $exception TYPE_NAME */
        try {
            $statement = $this->getDatabaseConnection()->prepare($sqlQuery);

            if (is_array($values) && empty($values)) {
                $statement->bindValue(':where', $value);
            }

            $affectedRows = null;
            $affectedRows = $statement->execute();

            if (method_exists($ar, 'afterDelete') &&
                in_array('afterDelete', $ar->getModelEvents())
            ) {
                $ar->{'afterDelete'}($ar);
            }

            return $affectedRows;

        } catch (\PDOException  $ex) {
            throw new \Exception($ex->getMessage());
        }

    }

    public function whereArray($columnName, $where = "", $type = null)
    {
        $resultArray = array();
        // Check whether value passed as array or not
        if (is_array($columnName)) {

            $arrayCount = count($columnName);
            $resultArray = $this->extractConditions($columnName);
            $arrayCount = count($resultArray);
            $i = 0;
            $whereValue = $whereCondition = "";

            foreach ($resultArray as $row) {

                $whereField = $this->quoteIdentifier(
                        self::getActiveRecordInstance()->getTableName()
                    ) . "." . $this->quoteIdentifier($row['0']) . "";

                if ($row['0'] === null) {
                    $whereField = '';
                }

                $whereCondition = (is_string($row['1'])) ? strtoupper($row['1']) : $row['1'];

                if (preg_match('/#/', $row['2'])) {
                    $whereValue = str_replace('#', '(', $row['2']) . ')';
                } else {
                    $whereValue = (is_string($row['2'])) ? " '" . $row['2'] . "'" : $row['2'];
                }

                $whereType = '';
                $this->_fromWhere .= $whereField . " " . $whereCondition . $whereValue;

                $whereType = ($where == '' && $type !== 'OR') ? ' AND ' : ' ' . $type . ' ';
                $this->_fromWhere .= ($i < $arrayCount - 1) ? $whereType : '';
                $this->_whereType = '';
                $i++;
            }

            return $this;
        }

        if (is_string($columnName)) {
            $columnName = "`" . $columnName . "`";
        }

        $this->_whereType = '=';
        $this->_columnWhere = $this->quoteIdentifier(
                self::getActiveRecordInstance()->getTableName()
            ) . '.' . $columnName;
        $this->_fromWhere = " '" . $where . "' ";

        if (!is_null($type)) {
            $this->_whereType = $type;
        }

        return $this;
    }

    private function extractConditions($arr)
    {
        $pattern = '/([\w]+)?[\s]?([\!\<\>]?\=|[\<\>]|[cs]{0,1}like|not
                    |i[sn]|between|and|or)?/i';

        $result = array();
        foreach ($arr as $key => $value) {

            preg_match($pattern, $key, $matches);
            $matches[1] = !empty($matches[1]) ? $matches[1] : null;
            $matches[2] = !empty($matches[2]) ? $matches[2] : null;

            $result [] = array($matches[1], $matches[2], $value);
        }

        return $result;
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

    /*
     * Extract user conditions from array
     * @access private
     * @param $arr array to extract conditions
     * @return array
     */

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

    /*
     * Get the distinct value of the column
     * @access public
     * @param $column
     * @return $this
     *
     */

    private function createPlaceHolder($arguments)
    {
        foreach (array_keys($arguments) as $key) {
            $placeholder[] = substr(str_repeat('?,', count($key)), 0, -1);
        }
        return implode(',', $placeholder);
    }

    /*
    * limit function to limit the database query
    * @access   public
    * @param    int
    * @return   object
    */

    /**
     * Adding an element in the where array with the value
     * to the bindings
     *
     * @access public
     * @param String $key
     * @param String $oper
     * @param String $value
     * @return void
     */
    public function orWhere($key, $operator, $value)
    {
        $this->where[] = "OR " . $key . ' ' . $operator . ' ' . "?";
        $this->bindings[] = $value;

        return $this;
    }

    public function orWhereIn($key, $value, $operator = 'IN')
    {
        $exp = explode(',', $value);
        $this->where[] = "OR OR" . $key . ' ' . $operator . ' (' . $this->createPlaceHolder($exp) . ') ';

        foreach ($exp as $key => $val) {
            $this->bindings[$key] = $val;
        }

        return $this;
    }

    /*
    * Group By function to group columns based on aggregate functions
    * @access   public
    * @param    string
    * @return   object
    */

    public function distinct($column)
    {
        $this->distinct = (string)(strtolower(__FUNCTION__) . ($column));

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

    /*
    * orderBy function to make order for selected query
    * @access   public
    * @param    string
    * @param    string
    * @return   object
    */

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
        $limit = "";
        $ar = self::getActiveRecordInstance();

        if (method_exists($ar, 'beforeSelect') &&
            in_array('beforeSelect', $ar->getModelEvents())
        ) {
            $ar->{'beforeSelect'}($ar);
        }

        $this->buildQuery();
        try {
            $statement = $this->getDatabaseConnection()->prepare($this->sqlQuery);
            $this->sqlQuery = null;
            $this->setDbStatement($ar->getDatabase(), $statement);

            // Bind parameters
            for ($i = 1; $i <= count($this->bindings); $i++) {
                $statement->bindParam($i, $this->bindings[$i - 1]);
            }

            //$statement->bindValue(':where', $this->_fromWhere);
            $statement->execute();
            $data = $this->fetchAs($statement, $fetchMode);

            if (method_exists($ar, 'afterSelect') &&
                in_array('afterSelect', $ar->getModelEvents())
            ) {
                $ar->{'afterSelect'}($ar);
            }

            //show($data);
            if ($statement->rowCount() > 0) {
                //$ar->attributes = array();
                return new Collection($data); //new \ArrayObject($data);
            } else {
                return null;
            }

        } catch (PDOException $ex) {
            throw new \Exception("Database exceptions: Invalid query x" . $ex->getMessage());
        }
    }

    private function buildQuery()
    {
        // Ignore columns while selecting from database
        if (method_exists(self::getActiveRecordInstance(), 'exceptColumns')) {
            $this->prepareExceptColumns();
        }

        return $this->buildSqlQuery();
    }

    private function prepareExceptColumns()
    {
        $ar = self::getActiveRecordInstance();
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

    private function buildSqlQuery()
    {
        $this->sqlQuery =
            'SELECT ' . $this->buildSelectedColumns() . ' FROM ' . $this->quoteIdentifier(
                self::getActiveRecordInstance()->getTableName()
            ) . ' ' . $this->_tableAlias . $this->getJoinSource() . $this->getWhere() .
            ' ' . $this->getGroupBy() . ' ' . $this->getOrderBy() . ' ' . $this->getLimit();

        return $this;
    }

    private function buildSelectedColumns()
    {
        if (is_null($this->_selectColumns)) {
            $this->_selectColumns = '*';
        }

        return ($this->_selectColumns == '*') ?
            $this->quoteIdentifier(
                self::getActiveRecordInstance()->getTableName()
            ) . ' ' . $this->_selectColumns : $this->_selectColumns;
    }

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

    /*
     * fetch data as user defined format
     *
     * @access private
     * @param  object $statement
     * @param  string $fetchMode null
     * @return mixed.
     */

    public function getGroupBy()
    {
        return (isset($this->_groupBy) && !is_null($this->_groupBy)) ?
            'GROUP BY ' . $this->_groupBy : '';
    }

    /*
     * Get number of rows returned by query
     *
     * @access public
     * @return int.
     */

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

    private function setDbStatement($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function fetchAs($statement, $fetchMode = null)
    {
        $data = array();

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
                $data = $statement->fetchAll(\PDO::FETCH_CLASS, '\\' . self::getActiveRecordInstance()->modelClassNs);
                break;
        }

        return $data;

    }

    public function rowCount()
    {
        $statement = $this->getDbStatement(self::getActiveRecordInstance()->getDatabase());

        return $statement->rowCount();
    }

    public function getDbStatement($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : null;
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

    public function execute()
    {
        return $this->_statement->execute();
    }

    public function fetch()
    {
        return $this->_statement->fetch();
    }

    ########

    /**
     * get all rows of table
     *
     * @access   public
     * @internal param \Cygnite\Database\fetch $fetchModel type
     * @return array results
     */
    public function getAll()
    {
        return $this->_statement->fetchAll(\PDO::FETCH_CLASS, get_class(self::getActiveRecordInstance()));
    }

    /*
    * Execute user raw queries
    *
    * @access public
    * @return array results
    */

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

    /*
    * Find single row
    *
    * @access public
    * @return array results
    */

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
     * whether the reader is closed or not.
     *
     * @return boolean whether the reader is closed or not.
     */
    private function isClosed()
    {
        return $this->closed;
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        $statement = null;
        $statement = $this->getDbStatement(self::getActiveRecordInstance()->getDatabase());

        $statement->closeCursor();
        $this->pdo = null;
        $this->closed = true;
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
                    //return static::$ar->fluentQuery()->select('all')->whereArray($params, '', 'OR')->findAll();
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
     * @param $type
     * @throws \Exception
     * @internal  param $string
     * @return object
     */
    public function select($column)
    {
        //select columns
        if (is_string($column) && !is_null($column)) {
            $this->_select($column);
        } else {
            throw new Exception("Accepted parameters should be string.");
        }

        return $this;
    }

    private function _select($column)
    {
        if ($column === 'all' || $column == '*') {
            $this->_selectColumns = $this->quoteIdentifier(
                    self::getActiveRecordInstance()->getTableName()
                ) . '.*';
        } else {

            if (strpos($column, 'AS') !== false || strpos($column, 'as') !== false) {
                return $this->selectExpr($column);
            }

            $this->_selectColumns = (string)str_replace(' ', '', $this->quoteIdentifier(explode(',', $column)));
        }
    }

    /*
    * Flush results after data retrieving process
    * It will unset all existing properties and close reader in order to make new selection process
    *
    */

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

    public function findBySql($arguments)
    {
        $results = array();
        $this->setDatabaseConnection(self::getActiveRecordInstance()->getDatabase());
        $fetchObject = $this->getDatabaseConnection()->prepare(trim($arguments[0]));
        $fetchObject->execute();
        $results = $this->fetchAs($fetchObject);

        return new Collection($results);
    }

    public function from($table = null)
    {
        $this->fromTable = (is_null($table)) ? get_class($this) : $table;

        return $this;
    }

    public function getFormTable()
    {
        return isset($this->formTable) ? $this->formTable : null;
    }

    public function callFinder($method, array $params)
    {
        return $this->find($method, $params);
    }

    public function find($method, $options = array())
    {
        if (isset($options['primaryKey'])) {

            $id = array_shift($options['args']);
            $options = array();

            return $this->select('all')
                ->where(self::getActiveRecordInstance()->getPrimaryKey(), '=', $id)
                ->orderBy(self::getActiveRecordInstance()->getPrimaryKey(), 'DESC')
                ->findAll();
        }

        return $this->{$method}($options);
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

    private function buildWhere()
    {
        $where = ($this->_columnWhere) ?
            '  WHERE  ' . $this->_columnWhere . ' =  :where ' : ' ';

        $where = (is_null($this->_columnWhere) && is_null($this->_fromWhere)) ? ''
            : ' WHERE  ' . $this->_columnWhere . " $this->_whereType " . $this->_fromWhere . "";

        return $where;
    }

    private function buildOriginalQuery()
    {
        return
            'SELECT ' . $this->buildSelectedColumns() . ' FROM ' . $this->quoteIdentifier(
                self::getActiveRecordInstance()->getTableName()
            ) . ' ' . $this->getWhere() .
            ' ' . $this->getGroupBy() . ' ' . $this->getOrderBy() . ' ' . $this->getLimit();
    }

    /**
     * Find all values from the database
     *
     * @param $arguments
     * @return mixed
     */
    private function all($arguments)
    {
        if (isset($arguments[0]['orderBy'])) {
            $exp = array();
            $exp = explode(' ', $arguments[0]['orderBy']);
            $this->orderBy(explode(',', $exp[0]), (isset($exp[1])) ? $exp[1] : 'ASC');
        } else {
            $this->orderBy(self::getActiveRecordInstance()->getPrimaryKey());
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
        if (isset($arguments[0]['paginate']) || method_exists(self::getActiveRecordInstance(), 'pageLimit')) {

            $page = $offset = $start = "";
            $offset = self::getActiveRecordInstance()->perPage; //how many items to show per page
            $limit = !isset($arguments[0]['paginate']['limit']) ?
                self::getActiveRecordInstance()->pageLimit() :
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

    private function first()
    {
        return $this->findFirstOrLast();
    }

    private function findFirstOrLast($order = null)
    {
        $orderBy = (!is_null($order)) ? $order : 'ASC';

        $fetchObject = $this->select('all')
            ->orderBy(self::getActiveRecordInstance()->getPrimaryKey(), $orderBy)
            ->limit(1)
            ->findAll();

        if ($fetchObject == null) {
            return self::getActiveRecordInstance()->returnEmptyObject();
        }

        return $fetchObject;
    }

    private function last()
    {
        return $this->findFirstOrLast('DESC');
    }

    private function findBy($arguments)
    {
        $fetch = $this->select('*')->where($arguments[0], $arguments[1], $arguments[2])->findAll();

        if ($fetch == null) {
            return self::getActiveRecordInstance()->returnEmptyObject();
        }

        return $fetch;
    }
}
