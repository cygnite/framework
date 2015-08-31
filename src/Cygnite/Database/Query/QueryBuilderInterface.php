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

/**
 * Database ActiveRecord.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

interface QueryBuilderInterface
{
    /**
     * Get Cyrus ActiveRecord instance
     *
     * @return null
     */
    public static function cyrus();

    /**
     * Get Database Connection Object based on database name
     * provided into model class
     *
     * @return null|object
     */
    public function getDatabaseConnection();

    /**
     * Insert a new row into table
     *
     * @param array $arguments
     * @return mixed
     * @throws \RuntimeException
     */
    public function insert($arguments = []);

    /**
     * Update table with data
     *
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function update($args);

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
    public function trash($where = null, $multiple = false);

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
    public function where($key, $operator, $value);

    /**
     * Adding an element in the where array with the value
     * to the bindings
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function whereIn($key, $value);

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
    public function orWhere($key, $operator, $value);

    /**
     * @param        $key
     * @param        $value
     * @param string $operator
     * @return $this
     */
    public function orWhereIn($key, $value, $operator = 'IN');

    /**
     * Get the distinct value of the column
     *
     * @access public
     * @param $column
     * @return $this
     *
     */
    public function distinct($column);

    /**
     * 
     * @param type $limit
     * @param type $offset
     */
    public function limit($limit, $offset = "");

    /**
     * orderBy function to make order for selected query
     *
     * @param        $column
     * @param string $orderType
     * @return $this
     * @throws \Exception
     */
    public function orderBy($column, $orderType = "ASC");

    /**
     * Group By function to group columns based on aggregate functions
     *
     * @param $column
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function groupBy($column);

    /**
     * Add an alias for the main table to be used in SELECT queries
     */
    public function tableAlias($alias);

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
    public function findAll($fetchMode = "");
    
    /**
     * This is alias method of findAll()
     *
     * @param string $fetchMode
     * @return mixed
     */
    public function findMany($fetchMode = "");
    
    /**
     * This method is alias of findAll, We will get only the
     * zeroth row from the collection object
     *
     * @return object|null
     */
    public function findOne($fetchMode = "");

    /**
     * Get row count
     *
     * @return mixed
     */
    public function rowCount();

    /**
     * Build raw queries
     *
     * @access public
     * @param  string $sql
     * @param  array  $attributes
     * @throws \Exception|\PDOException
     * @return object                   pointer $this
     */
    public function query($sql, $attributes = []);

    /**
     * @return mixed
     */
    public function execute();

    /**
     * Will return the single row 
     * from the table
     * 
     * @return mixed
     */
    public function get();

    /**
     * Get all rows of table as Collection
     *
     * @access   public
     * @internal param \Cygnite\Database\fetch $fetchModel type
     * @return array results
     */
    public function getAll();

    /**
     * @param string     $req       : the query on which link the values
     * @param array      $array     : associative array containing the values ??to bind
     * @param array|bool $typeArray : associative array with the desired value for its
     *                              corresponding key in $array
     * @link http://us2.php.net/manual/en/pdostatement.bindvalue.php#104939
     */
    public function bindArrayValue($req, $array, $typeArray = false);

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
    public function select($column);

    /**
     * Add an unquoted expression to the list of columns returned
     * by the SELECT query. The second optional argument is
     * the alias to return the column as.
     */
    public function selectExpr($expr);

    /**
     * Find result using raw sql query
     *
     * @param $arguments
     * @return Collection
     */
    public function findBySql($arguments);

    public function from($table);


    /**
     * Set table to run fluent query without model class
     *
     * @param $table
     * @return $this
     */
    public function table($table);

    /**
     * Find a single row
     *
     * @param       $method
     * @param array $options
     * @return mixed
     */
    public function find($method, $options = []);

    /**
     * We will return last executed query
     *
     * @return string
     */
    public function lastQuery();

    public function flush();

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close();

    /**
     * Find all values from the database table
     *
     * @param $arguments
     * @return mixed
     */
    public function all($arguments);

    /**
     * We will get first row of table
     *
     * @return mixed
     */
    public function first();

    /**
     * Get last row of table
     *
     * @return mixed
     */
    public function last();
}
