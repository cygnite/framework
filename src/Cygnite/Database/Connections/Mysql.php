<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database\Connections;

class Mysql extends Connector
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public function create()
    {
        $connection = parent::create();

        if (isset($this->config['unix_socket'])) {
            $connection->exec("use `{$this->config['database']}`;");
        }

        $this->setNamesAndCollation($connection);


        return $connection;
    }

    private function setNamesAndCollation($connection)
    {
        $names = "set names '".$this->config['charset']."'".
            (!is_null($this->config['collation']) ? " collate '".$this->config['collation']."'" : '');

        $connection->prepare($names)->execute();
    }
}
