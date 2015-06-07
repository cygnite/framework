<?php
namespace Cygnite\Mvc\View;

use Cygnite\AssetManager\Asset;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Reflection;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class Template
 * This file is used to Define all necessary configurations for twig template engine
 *
 * @package Cygnite\Mvc\View
 */
class Template
{
    public $properties;
    /**
     * @var view
     */
    private $view;
    private $reflection;

    // Set default functions to twig engine
    private $functions;
    private $validProperties = [
        'twigLoader',
        'autoReload',
        'twigDebug',
        'layout',
        'templateExtension'
    ];

    /**
     * @param            $view
     * @param Reflection $reflection
     */
    public function init($view, Reflection $reflection)
    {
        \Twig_Autoloader::register();

        $this->view = $view;

        if ($reflection instanceof Reflection) {
            $this->reflection = $reflection;
            $this->reflection->setClass($this->view);
        }

        // we will make accessible all valid properties
        foreach ($this->validProperties as $key => $property) {

            if (property_exists($this->view, $property)) {
                $this->setPropertyAccessible($property);
            }
        }
    }

    /**
     * @param $property
     */
    public function setPropertyAccessible($property)
    {
        $this->properties[$property] = $this->reflection->makePropertyAccessible($property);
    }

    /**
     * @return \Twig_Environment
     */
    public function setEnvironment()
    {
        $this->properties['twigLoader'] = new \Twig_Loader_Filesystem($this->view->views);

        $this->template = new \Twig_Environment($this->properties['twigLoader'], array(
            'cache' => CYGNITE_BASE . DS . APPPATH . DS . 'temp' . DS . 'twig' . DS . 'tmp' . DS . 'cache',
            'auto_reload' => $this->properties['autoReload'],
            'debug' => $this->properties['twigDebug'],
        ));

        $this->setDefaultFunctions();

        return $this->template;
    }

    public function setDefaultFunctions()
    {
        $this->setLink() // set link() function
            ->setTwigBaseUrl(); // set baseUrl() function

        foreach ($this->functions as $key => $func) {
            $this->template->addFunction($func);
        }
    }

    private function setTwigBaseUrl()
    {
        // We will set baseUrl as default function to twig engine
        $this->functions[] = $this->getTwigSimpleFunctionInstance(
            'baseUrl',
            function () {
                return Url::getBase();
            }
        );

        return $this;
    }

    public function getTwigSimpleFunctionInstance($name, $callback)
    {
        return new \Twig_SimpleFunction($name, $callback);
    }

    private function setLink()
    {
        // We will set default function to twig engine
        $this->functions[] = $this->getTwigSimpleFunctionInstance(
            'link',
            function ($link, $name = null, $attributes = []) {
                return Asset::link(str_replace('.', '/', $link), $name, $attributes);
            }
        );

        return $this;
    }

    /**
     * @param null $extension
     * @return void
     */
    public function addExtension($extension = null)
    {
        if ($extension == null) {
            $this->view->tpl->{__FUNCTION__}(new \Twig_Extension_Debug());
        } else {
            $this->view->tpl->{__FUNCTION__}($extension);
        }
    }

    /**
     * @param null     $funcName
     * @param callable $callback
     */
    public function addFunction($funcName = null, \Closure $callback = null)
    {
        if ($callback !== null && $callback instanceof \Closure) {
            $this->view->tpl->{__FUNCTION__}(new \Twig_SimpleFunction($funcName, $callback));
        } else {
            $this->view->tpl->{__FUNCTION__}($funcName, $callback);
        }
    }
}
