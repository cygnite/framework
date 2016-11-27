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

/**
 * Class Connector.
 */
class Connector
{
    /**
     * @var array
     */
    public $options = [
        PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE                 => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS         => PDO::NULL_NATURAL,
        PDO::ATTR_PERSISTENT           => true,
        PDO::ATTR_STRINGIFY_FETCHES    => false,
        PDO::ATTR_EMULATE_PREPARES     => false,
    ];

    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

     /**
     * Return PDO connection object. If connection lost will try again to connect.
     *
     * @return PDO
     */
    public function create()
    {
        $pdo = null;
        try {
            $pdo = $this->connect();
        } catch (\Exception $e) {
            $pdo = null;
            if ($this->isLostConnection($e)) {
                $pdo = $this->reconnect();
            }

            throw new \LogicException('Connection lost and no reconnector available.');
        }

        return $pdo;
    }

    /**
     * Recreate connection object.
     *
     * @return PDO
     */
    public function reconnect()
    {
        return $this->connect();
    }

    /**
     * Create connection
     * @return PDO
     */
    public function connect()
    {
        return new PDO($this->getDsn(), $this->config['username'], $this->config['password'], $this->getOptions());
    }

    /**
     * Get DSN string.
     *
     * @return string
     */
    public function getDsn()
    {
        return ($this->config['port'] !== '')
            ? "mysql:host={$this->config['hostname']};port={$this->config['port']};dbname={$this->config['database']}"
            : "mysql:host={$this->config['hostname']};dbname={$this->config['database']}";
    }

    /**
     * @param $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
