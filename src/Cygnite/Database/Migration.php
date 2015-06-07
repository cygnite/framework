<?php
namespace Cygnite\Database;

use Cygnite\Database\ActiveRecord;

/**
 * Class Migration
 * Seed your table with sample data using migration.
 *
 * @package Cygnite\Database
 */
class Migration extends ActiveRecord
{

    /**
     * Migration constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Seed a table using migration
     *
     * @param       $table
     * @param array $attributes
     * @return bool
     */
    public function insert($table, $attributes = [])
    {
        $this->tableName = $table;
        $this->setAttributes($attributes);

        if ($this->save()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete rows using migration
     *
     * @param       $table
     * @param array $attribute
     * @return bool
     */
    public function delete($table, $attribute)
    {
        $this->tableName = $table;

        if (is_array($attribute)) {
            return $this->trash($attribute, true);
        } else if (is_string($attribute) || is_int($attribute)) {
            return $this->trash($attribute);
        }
    }
}