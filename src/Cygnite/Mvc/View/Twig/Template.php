<?php

namespace Cygnite\Mvc\View\Twig;

use Cygnite\AssetManager\Asset;
use Cygnite\Common\UrlManager\Url;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class Template
 * This file is used to Define all necessary configurations for twig template engine.
 */
class Template
{
    public $methods;
    /**
     * @var view
     */
    private $view;
    // Set default functions to twig engine
    public $functions;

    private $validMethods = [
        'getAutoReload',
        'isDebugModeOn',
        'getTemplateExtension',
        'getLayout',
    ];

    public $twigEnvironment;

    /**
     * @param   $view
     */
    public function configure($view)
    {
        \Twig_Autoloader::register();
        $this->view = $view;
        /*
         | We will get all the necessary user configuration set
         | in controller by the user and set into template method array.
         | based on user provided configuration we will set twig environment
         */
        foreach ($this->validMethods as $key => $method) {
            if (method_exists($this->view, $method)) {
                $this->setValue($method);
            }
        }
    }

    /**
     * @param $method
     *
     * @internal param $property
     */
    public function setValue($method)
    {
        $this->methods[$method] = $this->view->{$method}();
    }

    /**
     * @return \Twig_Environment
     */
    public function setEnvironment()
    {
        $this->methods['twigLoader'] = new \Twig_Loader_Filesystem($this->view->getTemplateLocation());

        $this->twigEnvironment = new \Twig_Environment($this->methods['twigLoader'], [
            'cache'       => CYGNITE_BASE.DS.'public'.DS.'storage'.DS.'temp'.DS.'twig'.DS.'tmp'.DS.'cache',
            'auto_reload' => $this->methods['getAutoReload'],
            'debug'       => $this->methods['isDebugModeOn'],
        ]);
        $this->setDefaultFunctions();

        return $this->twigEnvironment;
    }

    /**
     * Set default functions for the framework.
     */
    public function setDefaultFunctions()
    {
        $this->setLink() //set link() function
             ->setTwigBaseUrl(); //set baseUrl() function

        foreach ($this->functions as $key => $func) {
            $this->twigEnvironment->addFunction($func);
        }
    }

    /**
     * register baseUrl function in twig.
     *
     * @return $this
     */
    private function setTwigBaseUrl()
    {
        // We will set baseUrl as default function to twig engine
        $this->functions[] = $this->getTwigSimpleFunctionInstance(
            'baseUrl', function () {
                return Url::getBase();
            }
        );

        return $this;
    }

    /**
     * @param $name
     * @param $callback
     *
     * @return \Twig_SimpleFunction
     */
    public function getTwigSimpleFunctionInstance($name, $callback)
    {
        return new \Twig_SimpleFunction($name, $callback);
    }

    /**
     * @return $this
     */
    private function setLink()
    {
        // We will set default function to twig engine
        $this->functions[] = $this->getTwigSimpleFunctionInstance(
            'link',
            function ($link, $name = null, $attributes = []) {
                return Asset::anchor(str_replace('.', '/', $link), $name, $attributes);
            }
        );

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTwigEnvironment()
    {
        return $this->twigEnvironment;
    }

    /**
     * @return \Twig_Extension_Debug
     */
    public function twigDebug()
    {
        return new \Twig_Extension_Debug();
    }

    /**
     * @param null $extension
     *
     * @return void
     */
    public function addExtension($extension = null)
    {
        if ($extension == null) {
            return $this->twigEnvironment->addExtension($this->twigDebug());
        }

        return $this->twigEnvironment->addExtension($extension);
    }

    /**
     * @param null     $funcName
     * @param callable $callback
     * @param callable $callback
     */
    public function addFunction($funcName = null, \Closure $callback = null)
    {
        if ($callback !== null && $callback instanceof \Closure) {
            return $this->twigEnvironment->addFunction($this->getTwigSimpleFunctionInstance($funcName, $callback));
        }

        return $this->twigEnvironment->addFunction($this->getTwigSimpleFunctionInstance($funcName, $callback));
    }

    /**
     * @param $function
     * @param $callback
     * @param array $options
     *
     * @return Twig_SimpleFilter
     */
    public function filter($function, $callback, $options = [])
    {
        return new \Twig_SimpleFilter($function, $callback, $options);
    }

    /**
     * @param $function
     * @param callable $callback
     * @param array    $options
     *
     * @return mixed
     */
    public function addFilter($function, $callback = null, $options = [])
    {
        $filter = $this->filter($function, $callback, $options);

        return $this->twigEnvironment->addFilter($filter);
    }

    /**
     * Add global variable available in all templates and macros.
     *
     * @param $name
     * @param $func
     *
     * @return mixed
     */
    public function addGlobal($name, $func)
    {
        return $this->twigEnvironment->addGlobal($name, $func);
    }
}
