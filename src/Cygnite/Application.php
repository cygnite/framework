<?php
namespace Cygnite;

use Closure;
use Cygnite\Strapper;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Url;
use Cygnite\Base\Router;
use Cygnite\Base\Dispatcher;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so that I can send you a copy immediately.
 *
 * @Package             :  Cygnite Framework BootStrap file
 * @Filename            :  cygnite.php
 * @Description         :  Bootstrap file to auto load core libraries initially.
 * @Author              :  Sanjoy Dey
 * @Copyright           :  Copyright (c) 2013 - 2014,
 * @Link	        :  http://www.cygniteframework.com
 * @Since	        :  Version 1.0
 * @File Source
 *
 */


class Application extends AutoLoader
{

    private static $instance;
	
	private $config;

    /**
     * ---------------------------------------------------
     * Cygnite Constructor
     * ---------------------------------------------------
     * Call parent init method
     */

    protected function __construct()
    {
		parent::init(Inflector::instance());
    }
	/*
	public function __callStatic($method, $arguments = array())
	{
		if ($method == 'load') {
			return call_user_func_array(array(new Application, $method), $arguments);
		}
	} */


    /**
     * ----------------------------------------------------
     * Return Singleton object or Closure instance of Cygnite
     * ----------------------------------------------------
     *
     * The loader method is used to return singleton object
     * of Cygnite
     *
     * @param callable Closure $callback
     * @return object
     */
	 public static function load(Closure $callback = null)
	 {
        if (!is_null($callback)) {
            return $callback(new Application);
        } else {
            if (self::$instance == null) {
                self::$instance = new Application;
            }

            return self::$instance;
        }
	 }
	 
    public function setConfig($config)
    {
		$this->config = $config;

        return $this;
    }
	
	public function getConfig()
	{
		return isset($this->config) ? $this->config : null;
	}
	/*
	 * @access public
	 * @@param $event event object
	 * @return void
	 */
	public function setEventInstance($event)
    {
		$this->event = $event;

        return $this;
    }
	/*
	 * @access public
	 * @param null
	 * @return event instance
	 */
	public function getEventInstance()
    {
		return isset($this->event) ? $this->event  : null;
    }

	/**
     * Get framework version
	 * @access public
	 */
    public static function version()
    {
        return CF_VERSION;
    }

    /**
     * @warning You can't change this!
     * @return string
     */
    public static function poweredBy()
    {
        return 'Cygnite Framework - '.CF_VERSION.' Powered by -
            Sanjoy Productions (<a href="http://www.cygniteframework.com">
            http://www.cygniteframework.com
            </a>)';
    }

    public function getDefaultConnection()
    {

    }

   /*
    * Set up framework constants and boot up
    * @bootstrap
    */
    public function initialize(Strapper $bootstrap)
    {
        $this->app = $bootstrap;

        return $this;
    }

    public function send(Router $router)
    {
       Url::instance($router);
       //Set up configurations for your awesome application
       Config::set('config_items', $this->config);echo $router->getBaseUrl() ;
       //Set URL base path.
       Url::setBase(
       	(Config::get('global_config', 'base_path') == '') ?  
       	    $router->getBaseUrl()  : 
       	    Config::get('global_config', 'base_path')
       	);
       	
       //initialize framework
       $this->app->init();
       $this->app->end($router);

      /**-------------------------------------------------------
       * Booting completed. Lets handle user request!!
       * Lets Go !!
       * -------------------------------------------------------
       */
        return new Dispatcher($router);
    }
}
