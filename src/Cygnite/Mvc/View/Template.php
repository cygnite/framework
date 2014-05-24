<?php
namespace Cygnite\Mvc\View;

use Cygnite\Reflection;
use Cygnite\AssetManager\Assets;

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
 * @Sub Packages          :
 * @Filename              :  Template
 * @Description           :  This file is used to Define all necessary configurations for template engine
 * @Author                :  Sanjoy Dey
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	              :  http://www.cygniteframework.com
 * @Since	              :  Version 1.0
 * @FileSource
 *
 */

class Template
{
    /**
     * @var view
     */
    private $view;

	public $properties;

	private $reflection;

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

		if (property_exists($this->view, 'twigLoader')) {
			$this->setPropertyAccessible('twigLoader');
        }
		if (property_exists($this->view, 'autoReload')) {
			$this->setPropertyAccessible('autoReload');
        }

		if (property_exists($this->view, 'twigDebug')) {
			$this->setPropertyAccessible('twigDebug');
        }

		if (property_exists($this->view, 'layout')) {
			$this->setPropertyAccessible('layout');
        }

		if (property_exists($this->view, 'templateExtension')) {
			$this->setPropertyAccessible('templateExtension');
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

		$this->template = new \Twig_Environment($this->properties['twigLoader'] , array(
            'cache' => getcwd().DS.APPPATH.DS.'temp'.DS.'twig'.DS.'tmp'.DS.'cache',
            'auto_reload' => $this->properties['autoReload'],
            'debug' => $this->properties['twigDebug'],
        ));

        $function = new \Twig_SimpleFunction('link',
            function ($link, $name = null, $attributes = array()) {
                return Asset::link(str_replace('.', '/', $link), $name, $attributes);
            }
        );

        $this->template->addFunction($function);

        return $this->template;
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
    public function addFunction($funcName = null,\Closure $callback = null)
    {
        if ($callback !== null && $callback instanceof \Closure) {
            $this->view->tpl->{__FUNCTION__}(new \Twig_SimpleFunction($funcName, $callback));
        } else {
            $this->view->tpl->{__FUNCTION__}($funcName, $callback);
        }
    }

}
