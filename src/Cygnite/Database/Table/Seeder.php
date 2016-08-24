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

abstract class Seeder
{
    /**
     * Seeder Command.
     *
     * @var
     */
    public $command;

    /**
     * Filter out other class and set only class to seed.
     *
     * @param $class
     */
    public function executeOnly($class)
    {
        /*
         | We will check if user requesting for seeding multiple table
         | Then we will filter out only those class to seed from the
         | specified seeder array
         */
        if ($exp = string_split($class, ',')) {
            $this->seeders = $this->filterClass($exp);
        } else {
            $hasClass = (in_array($class, $this->seeders)) ? true : false;

            if ($hasClass) {
                $this->seeders = $this->filterClass([$class]);
            }
        }
    }

    /**
     * Filter the class name from seeder array.
     *
     * @param array $class
     *
     * @return array
     */
    private function filterClass(array $class = [])
    {
        return array_intersect($this->seeders, $class);
    }

    /**
     * We will execute all seeder and populate database table.
     */
    public function execute()
    {
        $this->resolveSeeders($this->seeders);
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
     *
     * @return mixed
     */
    private function resolve($class)
    {
        $class = APP_NS.'\\Resources\\Database\\Seeding\\'.$class;

        $return = (new $class())->execute();

        if (isset($this->command)) {
            $this->command->info("Seeded: $class OK!");
        }

        return $return;
    }

    /**
     * @param $command
     */
    public function setSeederCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Run method is abstract therefore it must be implemented.
     *
     * @return mixed
     */
    abstract public function run();
}
