<?php

namespace Cygnite\Database\Exceptions;

use Cygnite\Database\Connection;
use PDOException;
use PDOStatement;

class DatabaseException extends PDOException
{
    public function __construct($exceptions)
    {
        if ($exceptions instanceof Connection) {
            parent::__construct(
                implode(', ', $exceptions->connection->errorInfo()),
                intval($exceptions->connection->errorCode())
            );
        } elseif ($exceptions instanceof PDOStatement) {
            parent::__construct(
                implode(', ', $exceptions->errorInfo()),
                intval($exceptions->errorCode())
            );
        }
    }
}
