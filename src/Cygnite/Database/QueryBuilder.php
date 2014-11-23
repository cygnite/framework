<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database;

use Exception;

class QueryBuilder
{
    private $connection;

    public function __construct(ActiveRecord $instance)
    {
        show($instance);
        if ($instance instanceof ActiveRecord) {

        }

    }

}
