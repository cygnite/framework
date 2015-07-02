<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Database\Table;

use Cygnite\Database\Connection;

abstract class Seeder
{
    /**
     * @param array $seeders
     */
    public function execute($seeders = [])
    {
        if (empty($seeders)) {
            $seeders = $this->seeders;
        }

        $this->resolveSeeders($seeders);
    }

    /**
     * @param $seeders
     */
    public function resolveSeeders($seeders)
    {
        foreach ($seeders as $key => $class) {
            $this->resolve($class);
        }
    }

    /**
     * @param $class
     * @return mixed
     */
    private function resolve($class)
    {
        return (new $class)->execute();
    }

    /**
     * Run method is abstract therefore it must be implemented
     * @return mixed
     */
    abstract public function run();
}
