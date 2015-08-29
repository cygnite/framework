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

use Cygnite\Reflection;
use Cygnite\Helpers\Inflector;
use Cygnite\Mvc\View\ViewInterface;
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

class View implements ViewInterface,\ArrayAccess
{
    use ControllerViewBridgeTrait, Output;

    public $twigTemplateLocation;

    public $data = [];

    public $tpl;

    public $twig;

    private $class;

    private $viewPath;

    private $params = [];

    protected $layout;

    protected $controllerView;

    protected $template;

    protected $templateEngine = false;

    protected $templateExtension = '.html.twig';

    protected $viewsFilePath = 'Views';

    protected $twigDebug = false;

    protected $autoReload = false;

    protected $widgetName;

    /**
     * @param Template $template
     */
    public function __construct(Template $template = null)
    {
        $this->template = $template;
    }

    /**
     * We will set the view directory path
     */
    private function setViewPath()
    {
        $viewPath = (strpos($this->viewsFilePath, '.') == true) ?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        $this->twigTemplateLocation = CYGNITE_BASE . DS . APP . DS . $viewPath . DS;
    }

    /**
     * We will set Twig Template Environment
     *
     * @internal param $template
     */
    private function setTwigEnvironment()
    {
        if ($this->template instanceof Template) {

            $this->template->configure($this);
            $this->setTemplate($this->template);
            $controller = $this->getControllerName();
            $this->layout = Inflector::toDirectorySeparator($this->layout);

            if ($this->layout == '') {
                $this->layout = strtolower($controller);
            }

            $this->setViewPath();
            $this->tpl = $this->template->setEnvironment();

            if ($this->isDebugModeOn()) {
                $this->template->addExtension();
            }
        }

        return $this;
    }

    public function getControllerName()
    {
        $exp = explode('.', str_replace(APP_NS.'.Views.', '', $this->widgetName));
        return isset($exp[0]) ? $exp[0] : '';
    }

    /**
    * Magic Method for handling dynamic data access.
     *
    * @param $key
    */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
    * Magic Method to save data into array.
     *
    * @param $key
    * @param $value
    */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Create view and render it. This is alias of render method
     *
     * <code>
     * View::create('view-name', $data);
     *
     * $view = View::create();
     * </code>
     *
     * @param       $view
     * @param array $data
     * @return mixed
     */
    public static function create($view = null, array $data = [])
    {
        $v = ViewFactory::make();

        if (is_null($view)) {
            return $v;
        }

        return $v->render($view, $data, true)->content();
    }


    /**
     * This function is alias of create method
     * If user want to access render function statically
     *
     * <code>
     * View::compose('view-name', $data);
     *
     * View::compose('view-name', $data, function ($view, $content)
     * {
     *      $view->setLayout('layouts.base');
     * });
     * </code>
     *
     * @param          $view
     * @param array    $data
     * @param callable $callback
     * @return mixed
     */
    public static function compose($view, array $data = [], \Closure $callback = null)
    {
        $v = ViewFactory::make();

        if ($callback instanceof \Closure) {

            $content = $v->render($view, $data, true)->content();

            return $callback($v, $content);
        }

        return $v->render($view, $data, true)->content();
    }

    /**
     * This function is to load requested view file
     *
     * Render PHP View With Layout:
     * -------------------------------
     * $this->render('Apps.Views.home:welcome', []);
     *
     * $content = $this->render('Apps.Views.home:welcome', [], true)->content();
     * return Response::make($content)->send();
     *
     * Render Twig template:
     * ---------------------
     * //path : Views/home/index.html.twig
     * $this->render('home.index', $data);
     * 
     * $content = $this->render('home.index', $data, true);
     * return Response::make($content)->send();
     * @param       $view
     * @param array $params
     * @param bool  $return
     * @throws Exceptions\ViewNotFoundException
     * @return $this|mixed
     */
    public function render($view, $params = [], $return = false)
    {
        /*
         * Check if template engine is set as true
         * then call template and return from here
         */
        if ($this->templateEngine !== false) {
            return $this->template($view, $params, $return);
        }

        $this->widgetName = $view;
        $this['__return_output__'] = $return;
        $this->__set('parameters', $params);
        $path = $this->getPath(Inflector::toDirectorySeparator($view));

        /*
         | Throw exception is view file is not readable
         */
        if (!file_exists($path) && !is_readable($path)) {
            throw new ViewNotFoundException("Requested view doesn't exists in path $path");
        }

        $this->layout = Inflector::toDirectorySeparator($this->getLayout());

        if (!is_null($this->layout) || $this->layout !== '') { // render view page into the layout
            $this->renderLayoutView($path, $this->viewsFilePath.DS, $params)->displayContent();

            return $this;
        }

        $this->viewPath = $path;
        $this->load()->displayContent();

        return $this;
    }

    /**
     * Render twig templates
     *
     * @param       $view
     * @param array $param
     * @param bool  $return
     * @return $this
     */
    public function template($view, array $param = [], $return = false)
    {
        $this->setTwigEnvironment();
        $path = $this->getPath(Inflector::toDirectorySeparator($view), true);

        /*
         | We will check if tpl is holding the object of
         | twig, then we will set twig template
         | environment
         */
        if (is_object($this->tpl) && is_file($path)) {
            $this->setTwigTemplateInstance($view);
        }

        /*
        | We will check is twig template instance exists
        | then we will render twig template with parameters
        */
        if (is_object($this->tpl) && is_object($this['twig_template'])) {
            return ($return) ?
                $this['twig_template']->render($param) :
                $this['twig_template']->display($param);
        }

        return $this;
    }

    private function displayContent()
    {
        if ($this['__return_output__'] == false) {
            echo $this['__content__'];
        }
    }

    /**
     * @param      $path
     * @param bool $twig
     * @return mixed|string
     */
    private function getPath($path, $twig = false)
    {
        if ($twig) {
            return CYGNITE_BASE . DS .APP.DS.$this->viewsFilePath. DS.$path . $this->templateExtension;
        }

        return str_replace(APP_NS, APP, CYGNITE_BASE . DS . $path . '.view' . EXT);
    }

    /**
     * @param $view
     * @return $this
     */
    private function setTwigTemplateInstance($view)
    {
        if (is_null($this['twig_template'])) {
            $this['twig_template'] = $this->tpl->loadTemplate(
                str_replace('.', DS, $view).$this->getTemplateExtension()
            );
        }

        return $this;
    }

    /**
     * @param $view
     * @param $path
     * @param $params
     * @return $this
     */
    private function renderLayoutView($view, $path, $params)
    {
        $layout = CYGNITE_BASE . DS . APP . DS .$path .$this->layout . '.view' . EXT;

        $data['yield'] = $this->renderView($view, $params);
        $output = $this->renderView($layout, array_merge($data, $params));
        $this['__content__'] = $output;

        return $this;
    }

    public function content()
    {
        return ($this->offsetGet('__content__') ? $this->offsetGet('__content__') : null);
    }

    /**
     * @param $params
     * @return mixed|string
     */
    public function with(array $params = [])
    {
        if (is_array($params)) {
            $this->params = (array)$params;
        }

        return $this->load();
    }

    /**
     * @return string
     * @throws ViewNotFoundException
     */
    private function load()
    {
        $data = [];
        $data = array_merge($this->params, $this->__get('parameters'));

        if (!file_exists($this->viewPath)) {
            throw new ViewNotFoundException('The view path ' . $this->viewPath . ' is invalid.');
        }

        $output = $this->renderView($this->viewPath, $data);
        $this['__content__'] = $output;

        return $this;
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([ViewFactory::make(), $method], [$params]);
    }

    /**
     * Handle undefined method errors.
     *
     * @param $method
     * @param $arguments
     * @throws \RuntimeException
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->validFlashMessage)) {
            return $this->setFlashMessage($method, $arguments);
        }

        throw new \RuntimeException("Method View::$method() doesn't exists");
    }

    /**
     * ArrayAccess
     *
     * @param mixed $offset
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
     * \ArrayAccess
     *
     * @param int|string $offset
     * @param mixed      $value
     * @return $this|void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;

        return $this;
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param $template
     * @return $this
     */
    private function setTemplate($template)
    {
        $this->twig = $template;

        return $this;
    }

    /**
     * Get Template instance
     *
     * @return null
     */
    public function getTemplate()
    {
        return isset($this->twig) ? $this->twig : null;
    }

    /**
     * @param $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLayout()
    {
        return (isset($this->layout) ? $this->layout : null);
    }

    /**
     * @param $templateEngine
     * @return $this
     */
    public function setTemplateEngine($templateEngine)
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
    public function getTemplateExtension()
    {
        return $this->templateExtension;
    }

    /**
     * @param $autoReload
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
     * We can set data into view page
     *
     * @param $key
     * @param $value
     */
    public function setData($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * @return array|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $name
     */
    public function setController($name)
    {
        $this->class = $name;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return isset($this->class) ? $this->class : get_called_class();
    }

    public function setContainer($container)
    {
        $this['container'] = $container;
    }

    public function getContainer()
    {
        return $this['container'];
    }

    public function setTwigViewPath($path)
    {
        $this->viewsFilePath = $path;

        return $this;
    }

    public function getTemplateLocation()
    {
        return $this->twigTemplateLocation;
    }
}
