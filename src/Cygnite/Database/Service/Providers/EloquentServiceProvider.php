<?php

namespace Cygnite\Database\Service\Providers;

use Cygnite\Container\Service\ServiceProvider;
use Cygnite\Database\Configure as Database;
use Cygnite\Foundation\Application;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as EloquentCapsule;
use Illuminate\Events\Dispatcher;

/**
 * If you want to use Eloquent ORM register EloquentServiceProvider
 * as Service and extend \Cygnite\Database\Service\Eloquent in your
 * model class.
 *
 * <code>
 * class User extends \Cygnite\Database\Service\Eloquent
 * {
 *
 *
 * }
 * </code>
 *
 * Class EloquentServiceProvider
 */
class EloquentServiceProvider extends ServiceProvider
{
    protected $app;

    public $eloquent;

    /**
     * Setup Eloquent ORM Service Provider.
     *
     * @param Application $app
     * @source https://github.com/illuminate/database
     */
    public function register(Application $app)
    {
        $this->eloquent = new EloquentCapsule();
        $config = Database::getDatabaseConfiguration();

        /*
         | We will loop over all connections
         | and set connection
         */
        foreach ($config as $key => $c) {
            $this->setConnection($c);
        }

        $this->eloquent->setEventDispatcher(new Dispatcher(new Container()));
        // Make this Capsule instance available globally via static methods... (optional)
        $this->eloquent->setAsGlobal();
        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $this->eloquent->bootEloquent();
    }

    /**
     * @param $c
     */
    private function setConnection($c)
    {
        $this->eloquent->addConnection([
            'driver'    => $c['driver'],
            'host'      => $c['host'],
            'database'  => $c['database'],
            'username'  => $c['username'],
            'password'  => $c['password'],
            'charset'   => $c['charset'],
            'collation' => $c['collation'],
            'prefix'    => $c['prefix'],
        ]);
    }
}
