<?php

namespace Cygnite\Database\Query;

use Cygnite\Helpers\Inflector;

/**
 * Class Joins.
 */
class Joins
{
    /**
     * @var array
     */
    protected $joinSources = [];

    /**
     * @var bool
     */
    protected $hasJoin = false;

    /**
     * Add a simple JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function join($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('', $table, $constraint, $tableAlias);
    }

    /**
     * Add an INNER JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function leftJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('LEFT', $table, $constraint, $tableAlias);
    }

    /**
     * Add an INNER JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function innerJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('INNER', $table, $constraint, $tableAlias);
    }

    /**
     * Add a LEFT OUTER JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function leftOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('LEFT OUTER', $table, $constraint, $tableAlias);
    }

    /**
     * Add an RIGHT OUTER JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function rightOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('RIGHT OUTER', $table, $constraint, $tableAlias);
    }

    /**
     * Add an FULL OUTER JOIN string to the query.
     *
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    public function fullOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource('FULL OUTER', $table, $constraint, $tableAlias);
    }

    /**
     * Query Internal method to add a JOIN string to the query.
     *
     * The join operators can be one of INNER, LEFT OUTER, CROSS etc - this
     * will be prepended to JOIN.
     *
     * firstColumn, operator, secondColumn
     *
     * Example: ['user.id', '=', 'profile.user_id']
     *
     * will compile to
     *
     * ON `user`.`id` = `profile`.`user_id`
     *
     * The final (optional) argument specifies an alias for the joined table.
     *
     * @param      $joinOperator
     * @param      $table
     * @param      $constraint
     * @param null $tableAlias
     *
     * @return $this
     */
    protected function addJoinSource($joinOperator, $table, $constraint, $tableAlias = null)
    {
        $joinOperator = trim("{$joinOperator} JOIN");
        $table = Inflector::tabilize($this->quoteIdentifier(lcfirst($table)));

        // Add table alias if exists
        if (!is_null($tableAlias)) {
            $table .= " {$tableAlias}";
        }

        // Build the constraint
        if (is_array($constraint)) {
            list($firstColumn, $operator, $secondColumn) = $constraint;
            $constraint = "{$firstColumn} {$operator} {$secondColumn}";
        }

        //$table = Inflector::tabilize(lcfirst($table));
        $this->hasJoin = true;
        $this->joinSources[] = "{$joinOperator} {$table} ON {$constraint}";

        return $this;
    }

    /**
     * Add a RAW JOIN string to the query.
     *
     * @param $query
     * @param $constraint
     * @param $tableAlias
     *
     * @return $this
     */
    public function rawJoin($query, $constraint, $tableAlias)
    {
        $this->hasJoin = true;

        // Add table alias if present
        if (!is_null($tableAlias)) {
            $tableAlias = $this->quoteIdentifier($tableAlias);
            $query .= " {$tableAlias}";
        }

        // Build the constraint
        if (is_array($constraint)) {
            list($firstColumn, $operator, $secondColumn) = $constraint;
            $firstColumn = $this->quoteIdentifier($firstColumn);
            $secondColumn = $this->quoteIdentifier($secondColumn);
            $constraint = "{$firstColumn} {$operator} {$secondColumn}";
        }

        $this->joinSources[] = "{$query} ON {$constraint}";

        return $this;
    }

    /**
     * Quote a string that is used as an identifier
     * (table names, column names or table.column types etc) or an array containing
     * multiple identifiers.
     *
     * @param $identifier
     *
     * @return string
     */
    protected function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            $result = array_map([$this, 'quoteOneIdentifier'], $identifier);

            return implode(', ', $result);
        }

        return Inflector::tabilize($this->quoteOneIdentifier(lcfirst($identifier)));
    }

    /**
     * Quote a string that is used as an identifier
     * (table names, column names Or table.column types etc).
     *
     * @param $identifier
     *
     * @return string
     */
    protected function quoteOneIdentifier($identifier)
    {
        $parts = explode('.', $identifier);
        $parts = array_map([$this, 'quoteIdentifierSection'], $parts);

        return implode('.', $parts);
    }

    /**
     * This method for quoting of a single
     * part of an identifier.
     *
     * @param $part
     *
     * @return string
     */
    protected function quoteIdentifierSection($part)
    {
        if ($part === '*') {
            return $part;
        }

        $quoteCharacter = '`';
        // double up any identifier quotes to escape them
        return $quoteCharacter.
        str_replace(
            $quoteCharacter,
            $quoteCharacter.$quoteCharacter,
            $part
        ).$quoteCharacter;
    }
}
