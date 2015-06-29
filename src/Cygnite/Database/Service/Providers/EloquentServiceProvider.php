<?php
namespace Cygnite\Database\Service\Providers;

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Cygnite\Foundation\Application;
use Cygnite\Database\Configure as Database;
use Cygnite\Container\Service\ServiceProvider;
use Illuminate\Database\Capsule\Manager as EloquentCapsule;

/**
 * If you want to use Eloquent ORM register EloquentServiceProvider
 * as Service and extend \Cygnite\Database\Service\Eloquent in your
 * model class
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
 *
 * @package Cygnite\Database\Service\Providers
 */
class EloquentServiceProvider extends ServiceProvider
{
    protected $app;

    public function register(Application $app)
    {
        $app['eloquent'] = $app->share (function($c) {

            $eloquent = new EloquentCapsule();
            $config = Database::getDatabaseConfiguration();

            $eloquent->addConnection([
                'driver'    => $config['driver'],
                'host'      => $config['host'],
                'database'  => $config['database'],
                'username'  => $config['username'],
                'password'  => $config['password'],
                'charset'   => $config['charset'],
                'collation' => $config['collation'],
                'prefix'    => $config['prefix'],
            ]);

            $eloquent->setEventDispatcher(new Dispatcher(new Container));
            // Make this Capsule instance available globally via static methods... (optional)
            $eloquent->setAsGlobal();
            // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
            $eloquent->bootEloquent();
        });
    }
}
