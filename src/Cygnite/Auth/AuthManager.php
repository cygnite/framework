<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Auth;

use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;

abstract class AuthManager
{
    /**
     * @var
     */
    public static $model;

    /**
     * Set the model class name to authenticate user.
     *
     * @param $model
     */
    public static function model($model)
    {
        static::$model = $model;
        $class = '\\'.get_called_class();

        return $class::make();
    }

    /**
     * get model class name.
     *
     * @return null
     */
    public function getModel()
    {
        return isset(static::$model) ? static::$model : null;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public static function getContainer()
    {
        return Application::instance();
    }

    /**
     * Get the model object to check user existance against database.
     *
     * @return object
     */
    public function user()
    {
        $app = self::getContainer();

        return $app->make(static::getModel());
    }

    /**
     * Get the current table name.
     *
     * @return mixed
     */
    public function table()
    {
        return Inflector::tabilize(Inflector::getClassNameFromNamespace(self::getModel()));
    }
}
