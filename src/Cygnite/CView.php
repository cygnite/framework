<?php
namespace Cygnite;

use Cygnite\Reflection;
use Cygnite\Helpers\Assets;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package               :  Packages
 * @Sub Packages          :  Cygnite
 * @Filename              :  CView
 * @Description           :  This class used to render your view page or
 *                           template.
 * @Author                :  Sanjoy Dey
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	              :  http://www.cygniteframework.com
 * @Since	              :  Version 1.0
 * @Filesource
 * @Warning               :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
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

                $ns = $controller = null;
                $ns = get_called_class();

                $controller = Inflector::instance()->getClassNameFromNamespace($ns);

                $this->layout = Inflector::instance()->toDirectorySeparator($this->layout);

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
    public function render($view, $ui_content = null)
    {

        $controller = Inflector::instance()->getClassNameFromNamespace(get_called_class());

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

        if (is_readable($path.self::$name[$view].'.view'.EXT)) {

            if ($ui_content == true) {
                self::$uiContent =$ui_content;
                $this->view_path = $path.self::$name[$view].'.view'.EXT;
                $this->loadView();
                return $this->content;
                //return $this;
            }

            $this->view_path = $path.self::$name[$view].'.view'.EXT;

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
                $path = str_replace(':', DS, $value);
                $this->{$key} = $path;
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
       // var_dump($this->tpl);exit;
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
            include $this->view_path;
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
