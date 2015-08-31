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
 * Cygnite Memcache Cache Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class MemcachedConnector
{
    /**
     * Connect memcache based on its host, port, weight.
     *
     * @false string $host
     * @false mix $port
     * @param string $host
     * @param string $port
     * @return void
     */
    public function create(array $servers)
    {
        $this->memcached = $this->getMemcachedInstance();

        if (empty($servers)) {
            throw new \RuntimeException("Empty server configuration passed!!");
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

    public function getMemcachedInstance($uniqueId = false)
    {
        return ($uniqueId) ? new Memcached($uniqueId) : new Memcached();
    }

    public function memcached()
    {
        return $this->memcached;
    }
}
