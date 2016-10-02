<?php

namespace Cygnite\Mvc\View;

use Cygnite\Bootstrappers\Paths;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Widget.
 */
class Widget implements \ArrayAccess
{
    /** @var array */
    public $widget = [];

    /** @var array */
    public $data = [];

    /** @var bool */
    protected $module = false;

    /** @var */
    protected $widgetName;

    /** @var string */
    protected $moduleDir = 'Modules';

    /** @var Paths */
    public $paths;

    /**
     * Widget constructor.
     *
     * @param Paths  $paths  Paths instance.
     * @param Output $output Output instance.
     */
    public function __construct(Paths $paths, Output $output)
    {
        $this->paths = $paths;
        $this->output = $output;
    }

    /**
     * Returns Output instance.
     *
     * @return Output
     */
    public function getOutput() : Output
    {
        return $this->output;
    }

    /**
     * Returns Paths instance.
     *
     * @return Paths
     */
    public function getPaths() : Paths
    {
        return $this->paths;
    }

    /**
     * Set widget name.
     *
     * @param $name
     */
    private function setWidgetName($name)
    {
        $this->widgetName = $name;
    }

    /**
     * Get widget name.
     *
     * @return null
     */
    private function getWidgetName()
    {
        return (isset($this->widgetName)) ? $this->widgetName : null;
    }

    /**
     * Create widget view and returns content.
     *
     * @param string $name The name of the widget.
     * @param array $data Data to be passed in widget.
     * @param \Closure|null $callback
     * @return string
     */
    public function make(string $name, array $data = [], \Closure $callback = null) : string
    {
        $this->setWidgetName($name);
        $this->data = $data;
        /*
         | If second param given as closure then we will
         | return callback
         */
        if (!is_null($callback) && $callback instanceof \Closure) {
            return $callback($this);
        }

        /*
         | return object
         */
        return $this->render();
    }

    /**
     * @param $bool set
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return bool
     */
    public function module() : bool
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
            $moduleName = $exp[0]; $view = $exp[1];
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

    /**
     * Check if isModule parameter passed true, then system will Module widget view
     * otherwise normal mvc view path;
     *
     * @param $widget
     * @param string $moduleName
     * @param bool $isModule
     * @return string
     */
    private function getWidgetPath($widget, $moduleName = '', $isModule = false)
    {
        $modulePath = 'Views';
        if ($isModule) {
            $modulePath = $this->moduleDir.DS.$moduleName.DS.'Views';
        }

        return $this->paths['app.path'].DS.$modulePath.DS.$widget.'.view.php';
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

        $output = $this->output->renderView($path, $this->data);
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
