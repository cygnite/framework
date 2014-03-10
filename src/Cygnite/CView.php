<?php
namespace Cygnite;

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
 * @Sub Packages          :  Base
 * @Filename              :  CFView
 * @Description           :  This file is used to map all routing of the cygnite framework
 * @Author                :  Sanjoy Dey
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	          :  http://www.cygniteframework.com
 * @Since	          :  Version 1.0
 * @Filesource
 *
 */

class CView
{
    //private $layout = array();
    protected $layout;

    protected $controllerView;

    private $view_path;

    private $results =array();

    public $requestedController;

    public $model;

    private $views;

    private static $name = array();

    private static $uiContent;

    private static $content;

    public $data =array();

    protected $template;

    protected $templateEngine = 'twig';

    protected $templateExtension = '.html.twig';

    private $viewsFilePath = 'views';

    public $twigLoader;

    public $twig;

    protected $twigDebug = false;

    protected $autoReload = false;

    public function __construct()
    {
        //var_dump($this->templateExtension);exit;
        $this->views = getcwd().DS.APPPATH.DS.$this->viewsFilePath.DS;

        if ($this->templateEngine !== false && $this->templateEngine == 'twig') {

            \Twig_Autoloader::register();
            $ns = $controller = null;
            $ns = get_called_class();
			//Application::load();
            $controller = Inflector::instance()->getClassName($ns);

            $this->layout = Inflector::instance()->toDirectorySeparator($this->layout);

            if ($this->layout == '') {
                $this->layout = strtolower($controller);
            }

            $this->twigLoader = new \Twig_Loader_Filesystem($this->views);


            $this->twig = new \Twig_Environment($this->twigLoader, array(
                'cache' => getcwd().DS.APPPATH.DS.'temp'.DS.'twig'.DS.'tmp'.DS.'cache',
                'auto_reload' => $this->autoReload,
                'debug' => $this->twigDebug,
            ));

            $function = new \Twig_SimpleFunction('addLink',
                function ($link, $name = null, $attributes = array()) {
                    return Assets::addLink(str_replace('.', '/', $link), $name, $attributes);
                }
            );

            $this->twig->addFunction($function);

            if ($this->twigDebug === true) {
                $this->addExtension();
            }
        }
    }

    public function addExtension($extension = null)
    {
        if ($extension == null) {
            $this->twig->{__FUNCTION__}(new \Twig_Extension_Debug());
        } else {
            $this->twig->{__FUNCTION__}($extension);
        }
    }

    
    /**
    * Magic Method for handling dynamic data access.
    */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
    * Magic Method for handling the dynamic setting of data.
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

        $controller = Inflector::instance()->getClassName(get_called_class());

        $controller =
            strtolower(str_replace('Controller' , '', $controller)
        );

        $path= getcwd().DS.APPPATH.DS.'views'.DS.$controller.DS;

        if (is_object($this->twig) &&
            is_file($path.$view.$this->templateExtension
            )
        ) {
            $this->template = $this->twig->loadTemplate(
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
                return self::$content;
            }

            $this->view_path = $path.self::$name[$view].'.view'.EXT;

            return $this;
        }

    }

    protected function createSections(array $sections)
    {
        $this->assignToProperties($sections);
    }

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

    public function with($arrayResult)
    {
       // var_dump($this->twig);exit;
        if (is_object($this->twig) && is_object($this->template)) {
            return $this->template->display($arrayResult);
        }

        if (is_array($arrayResult)) {
            $this->results = (array) $arrayResult;
            $this->assignToProperties($arrayResult);
        }

        return $this->loadView();
        
    }

    private function loadView()
    {
        try {
            include $this->view_path;
        } catch (\Exception $ex) {
            throw new \Exception('The Path '.$this->view_path.' is invalid.'.$ex->getMessage());
        }

        return $this->bufferOutput();
    }

    private function bufferOutput()
    {
        ob_start();
        $output = ob_get_contents();
        ob_get_clean();

        //$this->gzippedOutput();

        if (isset(self::$uiContent) && self::$uiContent == true) {
            self::$content =  $output;
        } else {
            return $output;
        }
        //ob_end_flush();
    }

    public function __destruct()
    {
        //ob_end_flush(); //ob_end_clean();
        //ob_get_flush();
        unset($this->results);
    }
}
