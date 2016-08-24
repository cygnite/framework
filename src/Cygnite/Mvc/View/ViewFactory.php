<?php

namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

abstract class ViewFactory
{
    public static $view;

    private static $app;

    public static function setApplication($app)
    {
        static::$app = $app;
    }

    public static function app()
    {
        return static::$app;
    }

    /**
     * @return mixed
     */
    public static function make()
    {
        $app = self::app();
        //var_dump(get_class($app));exit;
        if (is_null(static::$view)) {
            //static::$view = $app->resolve('cygnite.mvc.view.view');
            static::$view = $app->resolve('cygnite.mvc.view.view');
        }

        static::$view->setContainer($app);

        return static::$view;
    }

    /**
     * Create view and render it. This is alias of render method.
     *
     * <code>
     * View::create('view-name', $data);
     * </code>
     *
     * @param       $view
     * @param array $data
     *
     * @return mixed
     */
    public static function create($view = null, array $data = [])
    {
        if (is_null($view)) {
            return static::make();
        }

        return static::make()->render($view, $data);
    }
}
