<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Cache\Storage;

use Memcached;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite Memcached Cache Connection Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class MemcachedConnector
{
    protected $memcached;

    /**
     * Set Memcached Instance
     *
     * @param $memCached Memcached
     */
    public function __construct($memCached)
    {
        $this->memcached = $memCached;
    }

    /**
     * Connect Memcached based on its host, port, weight.
     *
     * @false    string $host
     * @false    mix $port
     * @param array $servers
     * @throws \RuntimeException
     * @internal param string $host
     * @internal param string $port
     * @return void
     */
    public function create(array $servers)
    {
        $this->memcached = $this->getMemcachedInstance();

        if (empty($servers)) {
            throw new \RuntimeException(sprintf("Empty configuration passed to %s::create() method.", __CLASS__));
        }

        foreach ($servers as $server) {
            $this->memcached->addServer(
                $server['host'], $server['port'], $server['weight']
            );
        }

        $status = $this->memcached->getVersion();

        if (in_array('255.255.255', $status) && count(array_unique($status)) === 1) {
            throw new \RuntimeException('Could not establish Memcached connection.');
        }

        return $this->memcached;
    }

    /**
     * Connection object
     *
     * @return Memcached
     */
    public function memcached()
    {
        return $this->memcached;
    }
}
