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

use Cygnite\Cache\StorageInterface;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite Redis Cache Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class Redis implements StorageInterface
{
    public function store($key, $value)
    {
    }

    public function get($key)
    {
    }

    public function destroy($key)
    {
    }
}
