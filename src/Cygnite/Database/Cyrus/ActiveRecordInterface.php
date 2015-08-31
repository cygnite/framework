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

interface ActiveRecordInterface extends \ArrayAccess
{
    /**
     * @param $argument
     * @return mixed
     */
    public static function find($argument);

    /**
     * @return mixed
     */
    public static function first();

    /**
     * @param array $arguments
     * @return mixed
     */
    public static function all($arguments = []);

    /**
     * @return mixed
     */
    public static function last();

    /**
     * @param $query
     * @return mixed
     */
    public static function findBySql($query);

    /**
     * @return mixed
     */
    public static function createLinks();

    /**
     * @return mixed
     */
    public static function lastQuery();

    /*
    * Set your table columns dynamically
    * @access public
    * @param $key hold your table columns
    * @param $value hold your table column values
    * @return void
    *
    */
    public function getModelEvents();

    /**
     * get table name
     *
     * @return null
     */
    public function getTableName();

    /**
     * @param array $attributes
     * @return mixed
     */
    public function setAttributes($attributes = []);

    /**
     * Get attributes array
     *
     * @return array|null
     */
    public function getAttributes();

    /**
     * @param      $arguments
     * @param bool $multiple
     * @return mixed
     */
    public function trash($arguments, $multiple = false);

    /** Check id is null or not.
     *  If null return true else false
     *
     * @return bool
     */
    public function isNew();

    /**
     * Get the primary key of table
     *
     * @return null|string
     */
    public function getKeyName();

    /**
     * @param $arguments
     * @return mixed
     */
    public function findByPK($arguments);

    /**
     * @param $key
     * @return mixed
     */
    public function getId($key);

    /**
     * Set the pagination limit
     *
     * @param null $number
     */
    public function setPageLimit($number = null);

    /**
     * @param $arguments
     * @return mixed
     */
    public function joinWith($arguments);

    /**
     * We will get Fluent Query Object
     * @return Query
     */
    public function fluentQuery();

    /**
     * Use Connection to build fluent queries against any table
     *
     * @param $database
     * @return mixed
     */
    public static function db($database);

    /**
     * Get Database Connection
     *
     * @param $database
     * @return mixed
     */
    public static function connection($database);

    /**
     * @return mixed
     */
    public function getPrimaryKey();
}
