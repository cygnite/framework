<?php
namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Widget implements \ArrayAccess
{
    public $widget = array();

    public $data = array();

    protected $module = false;

    protected $widgetName;

    /**
     * @param       $name
     * @param array $data
     */
    public function __construct($name, $data= array())
    {
        $this->widgetName = $name;
        $this->data = $data;
    }

    /**
     * @param          $var
     * @param callable $callback
     * @param array    $data
     * @return mixed
     */
    public static function make($var, \Closure $callback = null, $data = array())
    {
        /*
         | If second param given as closure then we will
         | return callback
         */
        if ($callback instanceof \Closure && !is_null($callback)) {
            return $callback(new Widget($var, $data));
        }

        $widget = new Widget($var, $data);
        /*
         | return object
         */
        return $widget->render();
    }

    /**
     * @param $bool
     */
    public function isModule($bool)
    {
        $this->module = $bool;
    }

    /**
     * @param bool $isModule
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
            $this->isModule($isModule);
        }

        /*
         | We will check if widget is cached, return if already cached
         */
        if ($this->has($this->widgetName)) {
           return $this->getWidget($this->widgetName);
        }

        $path = null;

        if ($this->module) {
            /*
             | If widget belongs to HMVC modules and
             | has ":" in the view name, we will think first param
             | as module name and second param as view name
             */
            if (string_has($this->widgetName, ':')) {

                $exp = array();
                $exp = explode(':', $this->widgetName);
                $moduleName = $exp[0];
                $view = $exp[1];
                $path = getcwd().DS.APPPATH.DS.'modules'.DS.$moduleName.DS.'Views'.DS.$view.'.view'.EXT;

                $this->widgetName= null;
                $this->module = false;
            }


        } else {

            /*
             | If widget not belongs to HMVC modules and
             | has ":" in the view name, we will convert name as path
             */
            if (string_has($this->widgetName, ':')) {
                $widget = null;
                $widget = str_replace(':', DS, $this->widgetName);
                $path = getcwd().DS.APPPATH.DS.'views'.DS.$widget.'.view'.EXT;
            }
        }

        $outputInstance = new Output($this->widgetName);
        $output = $outputInstance->buffer($path, $this->data)->clean();
        $this->setWidget($this->widgetName, $output);

        return $this->getWidget($this->widgetName);
    }

    public function __toString()
    {
        return $this->getWidget($this->widgetName);
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
     * @return null
     */
    public function getWidget($name)
    {
        return isset($this->widget[$name]) ? $this->widget[$name] : null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->widget[$key]) ? true :false;
    }

    /**
     * ArrayAccess
     * @param  int|string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess
     * @param  int|string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
