<?php
/*
 * This file is part of the Cygnite Framework package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Mvc\View;

use Cygnite\Container\Container;
use Cygnite\Mvc\View\Twig\Template;
use Cygnite\Mvc\ControllerViewBridgeTrait;
use Cygnite\Mvc\View\Exceptions\ViewNotFoundException;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * View Class.
 *
 * Render your view page or template
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class View extends Composer implements ViewInterface,\ArrayAccess
{
    use ControllerViewBridgeTrait;

    protected $class;

    public $data = [];

    protected $output;

    protected $template;

    protected $layout;

    public $twigTemplateLocation;

    protected $twigDebug = false;

    protected $autoReload = false;

    public static $twigEnvironment;

    protected $viewsFilePath = 'Views';

    protected $templateEngine = false;

    protected $templateExtension = '.html.twig';

    /**
     * Constructor of View class
     *
     * @param Template $template
     * @param Output $output
     */
    public function __construct(Template $template, Output $output)
    {
        $this->template = $template;
        $this->output = $output;
        $this->output->setView($this);
    }

    /**
     * Create View and return view content
     *
     * @param $view
     * @param array $data
     * @return string
     */
    public function create($view = null, array $data = []) : string
    {
        return parent::create($view, $data);
    }

    /**
     * @param $view
     * @param array $data
     * @param callable $callback
     * @return mixed
     */
    public function compose(string $view, array $data = [], \Closure $callback = null)
    {
        return parent::compose($view, $data, $callback);
    }

    /**
     * Render view page and return content to browser
     *
     * @param string $view
     * @param array $params
     * @param boolean $return
     * @return mixed
     */
    public function render(string $view, array $params = [], $return = false)
    {
        return parent::render($view, $params, $return);
    }

    /**
     * Magic Method to save data into array.
     *
     * @param $key
     * @param $value
     */
    public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic Method for handling dynamic data access.
     *
     * @param $key
     */
    public function &__get(string $key)
    {
        return $this->data[$key];
    }

    /**
     * Get the stored view content
     *
     * @return string output
     */
    public function content()
    {
        return $this->offsetGet('__content__') ? $this->offsetGet('__content__') : null;
    }

    /**
     * ArrayAccess.
     *
     * @param mixed $offset
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
     * \ArrayAccess.
     *
     * @param int|string $offset
     * @param mixed      $value
     *
     * @return $this|void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;

        return $this;
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
     * Set Container object
     *
     * @param $container
     */
    public function setContainer(Container $container) : View
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get Container Object
     * @return object
     */
    public function getContainer() : Container
    {
        return $this->container;
    }

    /**
     * Set layout
     *
     * @param $layout
     * @return $this
     */
    public function setLayout($layout) : View
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Returns layout name
     *
     * @return mixed
     */
    public function getLayout()
    {
        return isset($this->layout) ? $this->layout : null;
    }

    /**
     * Set template instance
     *
     * @param $template
     * @return $this
     */
    public function setTemplate($template) : View
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get Template instance.
     *
     * @return null
     */
    public function getTemplate()
    {
        return isset($this->template) ? $this->template : null;
    }

    /**
     * @param $templateEngine
     *
     * @return $this
     */
    public function setTemplateEngine($templateEngine) : View
    {
        $this->templateEngine = $templateEngine;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateEngine()
    {
        return $this->templateEngine;
    }

    /**
     * @param $templateExtension
     *
     * @return $this
     */
    public function setTemplateExtension($templateExtension)
    {
        $this->templateExtension = $templateExtension;

        return $this;
    }

    /**
     * @return string
     */
    public function getTwigEnvironment()
    {
        return static::$twigEnvironment;
    }

    /**
     * @return string
     */
    public function getTemplateExtension()
    {
        return $this->templateExtension;
    }

    /**
     * @param $autoReload
     *
     * @return $this
     */
    public function setAutoReload($autoReload)
    {
        $this->autoReload = $autoReload;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAutoReload()
    {
        return $this->autoReload;
    }

    /**
     * @param $debug
     *
     * @return $this
     */
    public function setTwigDebug($debug)
    {
        $this->twigDebug = $debug;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugModeOn()
    {
        return (bool) $this->twigDebug;
    }

    /**
     * We can set data into view page.
     *
     * @param $key
     * @param $value
     */
    public function set(string $key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Return stored view data
     *
     * @return array
     */
    public function get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Returns all stored view data
     *
     * @return array
     */
    public function all() : array
    {
        return $this->data;
    }

    /**
     * @param $name
     */
    public function setController(string $name)
    {
        $this->class = $name;
    }

    /**
     * @return string
     */
    public function getController() : string
    {
        return isset($this->class) ? $this->class : get_called_class();
    }

    /**
     * Twig view path
     *
     * @param $path
     * @return $this
     */
    public function setTwigViewPath($path)
    {
        $this->viewsFilePath = $path;

        return $this;
    }

    /**
     * Get template location
     *
     * @return mixed
     */
    public function getTemplateLocation()
    {
        return $this->twigTemplateLocation;
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
     * Handle undefined method errors.
     *
     * @param $method
     * @param $arguments
     *
     * @throws \RuntimeException
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->validFlashMessage)) {
            return $this->setFlashMessage($method, $arguments);
        }

        throw new \RuntimeException("Method View::$method() doesn't exists");
    }
}
