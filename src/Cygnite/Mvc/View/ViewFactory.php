<?php

namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

use Cygnite\Foundation\Application as App;

abstract class ViewFactory
{
    public static $view;

    /**
     * @return mixed
     */
    public static function make()
    {
        if (is_null(static::$view)) {
            $app = App::instance();
            static::$view = $app->resolve('cygnite.mvc.view.view');
        }

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
