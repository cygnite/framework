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
class Composer
{
    protected $template;

    protected $output;

    protected $viewPath;

    protected $widgetName;

    protected $params = [];

    protected $controllerView;

    /**
     * Create view and render it. This is alias of render method.
     *
     * <code>
     * View::create('view-name', $data);
     *
     * $view = View::create();
     * </code>
     *
     * @param       $view
     * @param array $data
     *
     * @return mixed
     */
    public function create($view = null, array $data = [])
    {
        if (is_null($view)) {
            return $this;
        }

        return $this->render($view, $data, true)->content();
    }

    /**
     * This function is alias of create method
     * If user want to access render function statically.
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
     *
     * @return mixed
     */
    public function compose(string $view, array $data = [], \Closure $callback = null)
    {
        if ($callback instanceof \Closure) {
            $content = $this->render($view, $data, true)->content();

            return $callback($this, $content);
        }

        return $this->render($view, $data, true)->content();
    }

    /**
     * This function is to load requested view file.
     *
     * Render PHP View With Layout:
     * -------------------------------
     * $this->render('Apps.Views.home:welcome', []);
     *
     * $content = $this->render('Apps.Views.home:welcome', [], true)->content();
     * return Response::make($content);
     *
     * Render Twig template:
     * ---------------------
     * //path : Views/home/index.html.twig
     * $this->render('home.index', $data);
     *
     * $content = $this->render('home.index', $data, true);
     * return Response::make($content);
     *
     * @param       $view
     * @param array $params
     * @param bool  $return
     *
     * @throws Exceptions\ViewNotFoundException
     *
     * @return $this|mixed
     */
    public function render(string $view, array $params = [], $return = false)
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

        if ($this->layout !== '') { // render view page into the layout
            $this->renderLayoutView($path, $this->viewsFilePath.DS, $params)->displayContent();

            return $this;
        }

        $this->viewPath = $path;
        $this->load()->displayContent();

        return $this;
    }

    /**
     * Render twig templates.
     *
     * @param       $view
     * @param array $param
     * @param bool  $return
     *
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
        if (is_object(static::$twigEnvironment) && is_file($path)) {
            $this->setTwigTemplateInstance($view);
        }

        /*
        | We will check is twig template instance exists
        | then we will render twig template with parameters
        */
        if (is_object(static::$twigEnvironment) && is_object($this['twig_template'])) {
            return ($return) ?
                $this['twig_template']->render($param) :
                $this['twig_template']->display($param);
        }

        return $this;
    }

    protected function displayContent()
    {
        if ($this['__return_output__'] == false) {
            echo $this['__content__'];
        }
    }

    /**
     * @param      $path
     * @param bool $twig
     *
     * @return mixed|string
     */
    protected function getPath($path, $twig = false)
    {
        if ($twig) {
            return $this->container->get('src').DS.$this->viewsFilePath.DS.$path.$this->templateExtension;
        }

        return $this->container->get('src').DS.$path.'.view.php';
    }

    /**
     * @param $view
     * @param $path
     * @param $params
     *
     * @return $this
     */
    protected function renderLayoutView($view, $path, $params)
    {
        $layout = $this->container->get('app.path').DS.$path.$this->layout.'.view.php';

        $data['yield'] = $this->output->renderView($view, $params);
        $output = $this->output->renderView($layout, array_merge($data, $params));
        $this['__content__'] = $output;

        return $this;
    }


    /**
     * @param $params
     *
     * @return mixed|string
     */
    public function with(array $params = [])
    {
        if (is_array($params)) {
            $this->params = (array) $params;
        }

        return $this->load();
    }

    /**
     * @throws ViewNotFoundException
     *
     * @return string
     */
    private function load()
    {
        $data = [];

        $data = array_merge($this->params, $this->__get('parameters'));

        if (!file_exists($this->viewPath)) {
            throw new ViewNotFoundException('The view path '.$this->viewPath.' is invalid.');
        }

        $output = $this->output->renderView($this->viewPath, $data);
        $this['__content__'] = $output;

        return $this;
    }

    /**
     * We will set Twig Template Environment.
     *
     * @internal param $template
     */
    public function setTwigEnvironment()
    {
        if (!$this->template instanceof Template) {
            return $this;
        }

        $this->template->configure($this);
        $controller = $this->getControllerName();
        $this->layout = Inflector::toDirectorySeparator($this->layout);

        if ($this->layout == '') {
            $this->layout = strtolower($controller);
        }

        $this->setViewPath();
        if (!is_object(static::$twigEnvironment)) {
            static::$twigEnvironment = $this->template->setEnvironment();
        }

        if ($this->isDebugModeOn()) {
            $this->template->addExtension();
        }
    }

    /**
     * @param $view
     *
     * @return $this
     */
    protected function setTwigTemplateInstance($view)
    {
        if (is_null($this['twig_template'])) {
            $this['twig_template'] = static::$twigEnvironment->loadTemplate(
                str_replace('.', DS, $view).$this->getTemplateExtension()
            );
        }

        return $this;
    }

    /**
     * We will set the view directory path.
     */
    private function setViewPath()
    {
        $viewPath = (strpos($this->viewsFilePath, '.') == true) ?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        $this->twigTemplateLocation = $this->container->get('app.path').DS.$viewPath.DS;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        $exp = explode('.', str_replace($this->container->get('app.namespace').'.Views.', '', $this->widgetName));

        return isset($exp[0]) ? $exp[0] : '';
    }
}
