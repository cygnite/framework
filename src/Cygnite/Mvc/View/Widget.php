<?php

namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Widget.
 */
class Widget extends Output implements \ArrayAccess
{
    public $widget = [];

    public $data = [];

    protected $module = false;

    protected $widgetName;

    protected $moduleDir = 'Modules';

    /**
     * @param       $name
     * @param array $data
     */
    public function __construct($name, array $data = [])
    {
        $this->setWidgetName($name);
        $this->data = $data;
    }

    private function setWidgetName($name)
    {
        $this->widgetName = $name;
    }

    private function getWidgetName()
    {
        return (isset($this->widgetName)) ? $this->widgetName : null;
    }

    /**
     * @param          $name
     * @param array    $data
     * @param callable $callback
     *
     * @return mixed
     */
    public static function make($name, array $data = [], \Closure $callback = null)
    {
        /*
         | If second param given as closure then we will
         | return callback
         */
        if (!is_null($callback) && $callback instanceof \Closure) {
            return $callback(new self($name, $data));
        }
        /*
         | return object
         */
        return (new self($name, $data))->render();
    }

    /**
     * @param $bool
     */
    public function setModule($bool)
    {
        $this->module = $bool;
    }

    public function module()
    {
        return ($this->module) ? true : false;
    }

    /**
     * We will setup module view path.
     *
     * @return string
     */
    protected function setupModule()
    {
        if (string_has($this->getWidgetName(), ':')) {
            $exp = [];
            $exp = explode(':', $this->getWidgetName());
            $moduleName = $exp[0];
            $view = $exp[1];
            $path = $this->getWidgetPath($view, $moduleName, true);
            $this->setWidgetName(null);
            $this->setModule(false);

            return $path;
        }
    }

    /**
     * Set up widget view path.
     *
     * @return string
     */
    protected function setupWidget()
    {
        /*
         | If widget not belongs to HMVC modules and
         | has ":" in the view name, we will convert name as path
         */

        if (string_has($this->getWidgetName(), ':')) {
            $widget = null;
            $widget = str_replace(':', DS, $this->getWidgetName());

            return $this->getWidgetPath($widget, '', false);
        }
    }

    private function getWidgetPath($widget, $moduleName = '', $isModule = false)
    {
        $modulePath = 'Views';
        if ($isModule) {
            $modulePath = $this->moduleDir.DS.$moduleName.DS.'Views';
        }

        return CYGNITE_BASE.DS.APP.DS.$modulePath.DS.$widget.'.view.php';
    }

    /**
     * @param bool $isModule
     *
     * @return null
     */
    public function render($isModule = false)
    {
        /*
         | In some case you may not want to write much code
         | in such case you have option to pass param into render
         | so that we will understand you are trying to invoke module view
         */
        if ($isModule) {
            $this->setModule($isModule);
        }

        /*
         | We will return if widget already cached
         */
        if ($this->has($this->getWidgetName())) {
            return $this->getWidget($this->getWidgetName());
        }

        $path = null;

        if ($this->module()) {
            /*
             | If widget belongs to HMVC modules and
             | has ":" in the view name, we will think first param
             | as module name and second param as view name
             */
            $path = $this->setupModule();
        } else {
            $path = $this->setupWidget();
        }

        $output = $this->renderView($path, $this->data);
        $this->setWidget($this->getWidgetName(), $output);

        return $output;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setWidget($name, $value)
    {
        $this->widget[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function getWidget($name)
    {
        return isset($this->widget[$name]) ? $this->widget[$name] : null;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->widget[$key]) ? true : false;
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Get data set into Widget.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
