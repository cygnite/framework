<?php
namespace Cygnite\Database\Exceptions;

use PDOStatement;
use PDOException;
use Cygnite\Database\Connection;

class DatabaseException extends PDOException
{
    public function __construct($exceptions)
    {
        if ($exceptions instanceof Connection) {
            parent::__construct(
                join(", ", $exceptions->connection->errorInfo()),
                intval($exceptions->connection->errorCode())
            );
        } elseif ($exceptions instanceof PDOStatement) {
            parent::__construct(
                join(", ", $exceptions->errorInfo()),
                intval($exceptions->errorCode())
            );
        }
    }
}
