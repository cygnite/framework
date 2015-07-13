<?php
namespace Cygnite\Database;

class Joins
{
    public function __call($name, $arguments)
    {
        $join = str_replace('Join', '', $name);

        if (method_exists($this, ucfirst($join))) {
        }
    }

    public function join($table, $conditions)
    {
    }
}
