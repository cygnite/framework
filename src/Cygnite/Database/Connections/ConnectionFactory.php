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

use PDO;
use Exception;

class ConnectionFactory
{
    public $drivers = [
        'MySql'  => 'Cygnite\Database\Connections\MySql',
        'Oracle' => 'Cygnite\Database\Connections\Oracle',
        'MsSql'  => 'Cygnite\Database\Connections\MsSql',
    ];

    protected $config = [];

    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function make($class)
    {
        $class = $this->drivers[$class];

        return (new $class($this->getConfig()))->create();
    }
}
