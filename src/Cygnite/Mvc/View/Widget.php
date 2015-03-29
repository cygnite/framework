<?php
namespace Cygnite\Mvc\View;

use Cygnite\Proxy\StaticResolver;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Widget extends StaticResolver implements \ArrayAccess
{
    public $widget = array();

    protected function make($name, $arguments = array())
    {
        if ($this->has($name)) {
            return $this->widget[$name];
        }

        if (strpos($name, '::') != false) {
            $expression = explode('::', $name);
        }

        //we will check is modules is available in given string
        // if not we will look for view widget into the normal views directory
        if (strpos($name, 'modules') !== false) {
            $views = $expression[0];
            $moduleViews = DS.'Views'.DS;
        } else {
            $moduleViews = '';
            $views = 'views'.DS.$expression[0];
        }

        $path = getcwd().DS.APPPATH.DS.$views.DS.$expression[1].$moduleViews;

        $v = isset($expression[2]) && string_has($expression[2], '.') ? str_replace('.', DS, $expression[2]) : '';

        $view = $path.$v.'.view'.EXT;

        Output::load($view, $arguments);
        $output = Output::endBuffer();

        return $this[$name] = $output;
    }

    protected function has($key)
    {
        return isset($this->data[$key]) ? true :false;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }}