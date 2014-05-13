<?php
namespace Cygnite\Database;

use Exception;

class QueryBuilder
{
    private $connection;

    public function __construct(ActiveRecord $instance)
    {
        show($instance);
        if ($instance instanceof ActiveRecord) {

        }

    }

}
