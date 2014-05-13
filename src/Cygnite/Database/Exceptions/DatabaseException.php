<?php
namespace Cygnite\Database\Exceptions;

use PDOStatement;
use PDOException;
use Cygnite\Database\Connections;

class DatabaseException extends PDOException
{
    public function __construct($exceptions)
    {
        if ($exceptions instanceof Connections) {
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