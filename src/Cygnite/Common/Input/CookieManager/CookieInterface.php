<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\Input\CookieManager;

interface CookieInterface
{
    /**
     * @param $cookie
     * @return mixed
     */
    public function get($cookie);

    /**
     * @return mixed
     */
    public function store();

    /**
     * @param $cookie
     * @return mixed
     */
    public function destroy($cookie);

    /**
     * @param $cookie
     * @return mixed
     */
    public function has($cookie);
}
