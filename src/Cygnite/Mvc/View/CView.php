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

class CView
{
    protected $layout;

    protected $controllerView;

    private $viewPath;

    private $params = array();

    public $views;

    private static $name = array();

    public $data = array();

    protected $template;

    protected $templateEngine = 'twig';

    protected $templateExtension = '.html.twig';

    protected $viewsFilePath = 'views';

    public $twigLoader;

    public $tpl;

    protected $twigDebug = false;

    protected $autoReload = false;

    public $twig;

    protected $widgetName;

    /**
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $this->setViewPath();

        if ($this->templateEngine !== false && $this->templateEngine == 'twig') {
            $this->setTwigEnvironment($template);
        }
    }

    /**
     * We will set the view directory path
     */
    private function setViewPath()
    {
        $viewPath = (strpos($this->viewsFilePath, '.') == true) ?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        $this->views = CYGNITE_BASE . DS . APPPATH . DS . $viewPath . DS;
    }

    /**
     * We will set Twig Template Environment
     *
     * @param $template
     */
    private function setTwigEnvironment($template)
    {
            if ($template instanceof Template) {
                $template->init($this, new Reflection);
                $this->setTemplate($template);

                $ns = $controller = null;
                $ns = get_called_class();
                $controller = str_replace('Controller', '', Inflector::getClassNameFromNamespace($ns));
                $this->layout = Inflector::toDirectorySeparator($this->layout);

                if ($this->layout == '') {
                    $this->layout = strtolower($controller);
                }

                $this->tpl = $template->setEnvironment();

                if ($this->twigDebug === true) {
                    $template->addExtension();
                }
            }
        }

    /**
     * @param $template
     */
    private function setTemplate($template)
    {
        $this->twig = $template;
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
    * Magic Method for handling dynamic data access.
     *
    * @param $key
    */
    public function __get($key)
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
     * Magic Method for handling errors.
     *
     */
    public function __call($method, $arguments)
    {
        throw new \Exception("Undefined method called by " . get_class($this) . ' Controller');
    }

    /*
    * This function is to load requested view file
    * @false string (view name)
    * @throws ViewNotFoundException
    */
    public function render($view, $params = array())
    {
        $controller = $viewPage = null;
        $this->widgetName = $view;
        $this->__set('parameters', $params);

        $controller = Inflector::getClassNameFromNamespace($this->getController());
        $controller = strtolower(str_replace('Controller', '', $controller));

        list($viewPath, $path) = $this->getPath($controller);

        /*
         | We will check if tpl is holding the object of
         | twig, then we will set twig template
         | environment
         */
        if (
            is_object($this->tpl) &&
            is_file(
                $path . $view . $this->templateExtension
            )
        ) {
            return $this->setTwigTemplateInstance($controller, $view);
        }

        $viewPage = $path . $view . '.view' . EXT;
        /*
         | Throw exception is file is not readable
         */
        if (!file_exists($viewPage) &&
            !is_readable($viewPage)
        ) {
            throw new ViewNotFoundException;('The Path ' . $path . $view . '.view' . EXT . ' is invalid.');
        }

            $this->layout = Inflector::toDirectorySeparator($this->layout);

                if ($this->layout !== '') { // render view page into the layout
                $this->renderViewLayout($viewPage, $viewPath, $params);
                    return $this;
                }

        $this->viewPath = $viewPage;
        $this->loadView();

        return $this;
    }

    /**
     * @param $controller
     * @return string
     */
    private function getPath($controller)
    {
        $viewPath = null;
        $viewPath = (strpos($this->viewsFilePath, '.') == true) ?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        return array($viewPath, CYGNITE_BASE . DS . APPPATH . DS . $viewPath . DS . $controller . DS);
    }

    /**
     * @param $controller
     * @param $view
     * @return $this
     */
    private function setTwigTemplateInstance($controller, $view)
    {
        $this->template = $this->tpl->loadTemplate(
            $controller . DS . $view . $this->templateExtension
        );

        return $this;
    }

    /**
     * @param $viewPage
     * @param $viewPath
     * @param $params
     */
    private function renderViewLayout($viewPage, $viewPath, $params)
    {
        $layout = CYGNITE_BASE . DS . APPPATH . DS . $viewPath . DS . $this->layout . '.view' . EXT;

        ob_start();
        // Render the view page
        extract($params);
        include $viewPage;

        $data = array();
        $data['yield'] = ob_get_contents();
        ob_get_clean();

        // Render the layout page
        extract($data);
        include $layout;
        $output = ob_get_contents();
        ob_get_clean();

        echo $output;
    }

    /**
     * @param $name
     * @return Output
     */
    private function makeOutput($name)
    {
        return new Output($name);
    }

    /**
     * @param $params
     * @return string
     */
    public function with($params)
    {
        if (is_object($this->tpl) && is_object($this->template)) {
            return $this->template->display($params);
        }

        if (is_array($params)) {
            $this->params = (array)$params;
        }

        return $this->loadView();
    }

    /**
     * @return string
     * @throws ViewNotFoundException
     */
    private function loadView()
    {
        $params = array();
        $params = array_merge($this->params, $this->__get('parameters'));

        try {
            ob_start();
            extract($params);
            include $this->viewPath;
            $output = ob_get_contents();
            ob_get_clean();

            echo $output;

        } catch (\Exception $ex) {
            throw new ViewNotFoundException('The view path ' . $this->viewPath . ' is invalid.' . $ex->getMessage());
        }
    }

    public function __destruct()
    {
        unset($this->params);
    }

    /**
     * If user want to access render function statically
     * CView::create('view-name', $params);
     *
     * @param $method
     * @param $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        if ($method == 'create') {
            $view = new CView(new Template);
            return call_user_func_array(array($view, 'render'), array($params));
        }
    }
}
