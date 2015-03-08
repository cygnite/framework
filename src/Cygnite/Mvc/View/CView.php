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
use Cygnite\AssetManager\Assets;
use Cygnite\Helpers\Inflector;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Form.
 *
 * Render your view page or template
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

class CView
{
    protected $layout;

    protected $controllerView;

    private $view_path;

    private $results =array();

    public $requestedController;

    public $model;

    public $views;

    private static $name = array();

    private static $uiContent;

    public $content;

    public $data =array();

    protected $template;

    protected $templateEngine = 'twig';

    protected $templateExtension = '.html.twig';

    protected $viewsFilePath = 'views';

    public $twigLoader;

    public $tpl;

    protected $twigDebug = false;

    protected $autoReload = false;

    public $twig;

    /**
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $viewPath = (strpos($this->viewsFilePath, '.') == true)?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        $this->views = getcwd().DS.APPPATH.DS.$viewPath.DS;

        if ($this->templateEngine !== false && $this->templateEngine == 'twig') {

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
     * @return null
     */
    public function getTemplate()
    {
        return isset($this->twig) ? $this->twig : null;
    }
    /**
    * Magic Method for handling dynamic data access.
    * @param $key
    */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
    * Magic Method to save data into array.
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
        throw new \Exception("Undefined method called by ".get_class($this).' Controller');
    }

    /*
    * This function is to load requested view file
    * @false string (view name)
    *
    */
    public function render($view, $values = array(), $ui_content = false)
    {

        $controller = Inflector::getClassNameFromNamespace(get_called_class());

        $controller =
            strtolower(str_replace('Controller' , '', $controller)
        );

        $viewPath = null;

        $viewPath = (strpos($this->viewsFilePath, '.') == true)?
            str_replace('.', DS, $this->viewsFilePath) :
            $this->viewsFilePath;

        $path= getcwd().DS.APPPATH.DS.$viewPath.DS.$controller.DS;

        if (is_object($this->tpl) &&
            is_file($path.$view.$this->templateExtension
            )
        ) {
            $this->template = $this->tpl->loadTemplate(
                $controller.DS.$view.$this->templateExtension
            );

            return $this;
        }

        if (!file_exists($path.$view.'.view'.EXT) &&
            !is_readable($path.$view.'.view'.EXT)
        ) {
            throw new \Exception('The Path '.$path.$view.'.view'.EXT.' is invalid.');
        }

        self::$name[strtolower($view)] = $view;
        $viewPage = '';
        $viewPage = $path.self::$name[$view].'.view'.EXT;

        if (is_readable($viewPage)) {

            $this->layout = Inflector::toDirectorySeparator($this->layout);

                if ($this->layout !== '') { // render view page into the layout
                    $layout = getcwd().DS.APPPATH.DS.$viewPath.DS.$this->layout.'.view'.EXT;
                    //$this->view_path = $path.self::$name[$view].'.view'.EXT; // $layout;

                    $this->assignToProperties($values);

                    ob_start();
                    include $viewPage;

                    $data = array();
                    $data['yield'] = ob_get_contents();
                    ob_get_clean();
                    extract($data);

                    include $layout;
                    $data = array();
                    $content = null;

                    $output = ob_get_contents();
                    ob_get_clean();

                    echo $output;

                    return $this;
                }



            if ($ui_content == true) {
                self::$uiContent =$ui_content;
                $this->view_path = $viewPage;
                $this->loadView();
                return $this->content;
            }

            $this->view_path = $viewPage;
            $this->loadView();

            return $this;
        }

    }

    /**
     * @param array $sections
     */
    protected function createSections(array $sections)
    {
        $this->assignToProperties($sections);
    }

    /**
     * @param $resultArray
     * @throws \Exception
     */
    private function assignToProperties($resultArray)
    {
        try {
            $path = "";
            foreach ($resultArray as $key => $value) {
                if (is_string($value)) {
                    $path = str_replace(':', DS, $value);
                }

                $this->{$key} = $path;
                $this->__set($key, $value);
                //$this->layout[$key] = $path;
            }
        } catch (\InvalidArgumentException $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * @param       $layout
     * @param array $results
     * @return string
     */
    public function setLayout($layout, array $results)
    {
        $trace = debug_backtrace();

        $this->requestedController = strtolower(
            str_replace(
                'Apps\\Controllers\\',
                '',
                $trace[1]['class']
            )
        );

        $this->assignToProperties($results);
        $this->layoutParams = $results;

        if (is_readable(
            $this->views.rtrim(
                str_replace(
                    array(
                        '.',
                        '/',
                        ':'
                    ),
                    DS,
                    $layout
                ).'.layout'
            ).EXT
        )
        ) {


            $this->view_path =
                $this->views.rtrim(
                    str_replace(
                        array(
                            '.',
                            '/',
                            ':'
                        ),
                        DS,
                        $layout
                    ).'.layout'
                ).EXT;
        }

        return $this->loadView();

    }

    /**
     * @param $page
     * @return string
     * @throws \Exception
     */
    public function renderPartial($page)
    {
        //$this->requestedController.
        $path = null;

        if (is_string($page) && strstr($page, '@')) {
            $page = str_replace('@', '', $page);
            $page = $this->{$page};
            $path= str_replace(array('//', '\\\\'), array('/', '\\'), $this->views.DS.$page).EXT;
        } else {
            $path= str_replace(
                array(
                    '//',
                    '\\\\'
                ),
                array(
                    '/',
                    '\\'
                ),
                $this->views.DS.$page
            ).EXT;
        }

        if (is_readable($path)) {
            include_once $path;
        } else {
            throw new \Exception('The Path '.$path.' is invalid.');
        }

        return $this->bufferOutput();
    }

    /**
     * @param $arrayResult
     * @return string
     */
    public function with($arrayResult)
    {
        if (is_object($this->tpl) && is_object($this->template)) {
            return $this->template->display($arrayResult);
        }

        if (is_array($arrayResult)) {
            $this->results = (array) $arrayResult;
            $this->assignToProperties($arrayResult);
        }

        return $this->loadView();

    }

    /**
     * @return string
     * @throws \Exception
     */
    private function loadView()
    {
        try {
            $output = Output::load($this->view_path);
            //include $this->view_path;
        } catch (\Exception $ex) {
            throw new \Exception('The Path '.$this->view_path.' is invalid.'.$ex->getMessage());
        }

        return $this->bufferOutput();
    }

    /**
     * @return string
     */
    private function bufferOutput()
    {
        ob_start();
        $output = ob_get_contents();
        ob_get_clean();

        if (isset(self::$uiContent) && self::$uiContent == true) {
            $this->content =  $output;
        } else {
            return $output;
        }
        //ob_end_flush();
    }

    public function __destruct()
    {
        unset($this->results);
    }
}
