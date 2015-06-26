<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Cache;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

interface StorageInterface
{
    /* Abstract store method of caching*/
    public function store($key, $value);

    /* Abstract the cache retrieving function */
    public function get($key);

    /* Abstract the cache destroy function */
    public function destroy($key);
}
