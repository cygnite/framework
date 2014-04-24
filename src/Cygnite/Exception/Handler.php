<?php
namespace Cygnite\Exception;

use Closure;
use Cygnite\Application;
use Exception;
use Tracy\Helpers;
use Tracy\Debugger;
use Cygnite\Helpers\Config;


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
 * @Package                     :  Cygnite
 * @Sub Packages          : Exception
 * @Filename                  :  Handler.php
 * @Description            :  This file is used to Define all necessary configurations for Tracy Exception handler Component
 * @Author                    :  Sanjoy Dey
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	                     :  http://www.cygniteframework.com
 * @Since	               :  Version 1.0
 * @FileSource
 *
 */

class Handler implements ExceptionInterface
{

    public $name = '';

    private static $style = 'pretty';

    protected $debugger;

    protected $enableMode;

    /**
     * @param $configurations
     * @return $this
     */
    public function enable($configurations)
    {
        if (MODE == 'development') {
            $this->enableDebuggerWith(Debugger::DEVELOPMENT, $configurations);
        } else {
            $this->enableDebuggerWith(Debugger::PRODUCTION, $configurations);
        }

        return $this;
    }

    /**
     * @param       $enableMode
     * @param array $config
     */
    private function enableDebuggerWith($enableMode, $config = array())
    {
        $this->enableLogging = $config['enable_logging'];

        if ($config['enable_logging'] == true) {
            $logPath = str_replace('.', '/', $config['log_path']).'/';
            Debugger::enable($enableMode, $logPath);
        } else {
            Debugger::enable($enableMode);
        }
    }

    /**
     * @return $this
     */
    public function setTitle()
    {
        $this->debugger->info[] = $this->name.Application::version();

        return $this;
    }

    /**
     * @param      $email
     * @param bool $enableEmailing
     * @return $this
     */
    public function setEmailAddress($email, $enableEmailing = false)
    {
        if ($enableEmailing == true) {
            Debugger::$email = $email;
        }

        return $this;
    }


    public function setCustomPage()
    {

    }

    /**
     * @return $this
     */
    private function setCollapsePaths()
    {
        $this->debugger->collapsePaths[] = dirname(dirname(__DIR__));

        return $this;
    }

    /**
     * @param $debugger
     * @return $this
     */
    public function setDebugger($debugger)
    {
        $this->debugger = $debugger;

        return $this;
    }

    /**
     * @return null
     */
    public function getTracyDebugger()
    {
        return isset($this->debugger) ? $this->debugger : null;
    }

    public static function register(Closure $callback)
    {
        if ($callback instanceof Closure) {
            return $callback(new Handler);
        }
    }

    public function run()
    {
        $handler = $this;

        if (!is_null($path = $handler->assetsPath())) {
            $handler->includeAssets($path);
        }

        $this->setCollapsePaths();

        //Add new panel to debug bar
        $this->addPanel(
            function($e) use($handler) {

                if (!is_null($e)) {

                    if ($handler->enableLogging == true) {
                        $this->log($e);
                    }
                    return array(
                        'tab' => $handler->name,
                        'panel' => '<h1>
                        <span class="heading-blue">
                        <a href="http://www.cygniteframework.com">'.$handler->name.' </a>
                        </span>
                        </h1>
                        <p> Version : '.Application::version().' </p>
                        '
                    );
                }
            }
        );
        $this->addPanel(
            function($e) {

                if (!$e instanceof \PDOException) {
                    return;
                }
                if (isset($e->queryString)) {
                    $sql = $e->queryString;
                }

                return isset($sql) ? array(
                    'tab' => 'SQL',
                    'panel' => $sql,
                ) : NULL;

            }
        );

    }

    public function getBlueScreenInstance()
    {

    }

    public function addPanel($callback)
    {

        $this->getTracyDebugger()->{__FUNCTION__}($callback);

        return $this;
    }

    public function addSqlPanel($callback)
    {
        $this->getTracyDebugger()->{__FUNCTION__}($callback);

    }

    public function getBar()
    {
        return Debugger::getBar();
    }

    public function log($e)
    {
        Debugger::log($e);
    }

    private function includeAssets($path)
    {
        $vendor = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $stylePath = $vendor."/tracy/tracy/src/Tracy/templates/bluescreen.css";

        if (!file_exists($stylePath)) {
            throw new Exception("Tracy debugger not found in vendor directory");
        }

        $contents = file_get_contents($path.self::$style.'.css');

        if( strpos(file_get_contents($stylePath), '@Author:Cygnite') == false) {
            @file_put_contents($stylePath,$contents, FILE_APPEND);
        }
    }

    public function assetsPath()
    {
        if (is_dir($path = $this->getAssetsPath())) return $path;
    }

    /**
     * Get the Tracy custom assets path.
     *
     * @return string
     */
    protected function getAssetsPath()
    {
        return __DIR__.DS.'assets'.DS;
    }

    /**
     * Enable and set configuration to Tracy Handler
     * Event will Trigger this method on runtime when any exception
     * occurs
     */
    public function handleException()
    {
        $config = Config::get('global_config');

        // Exception handler registered here. So it will handle all your exception
        // and throw you pretty format
        Handler::register(
            function($exception) use ($config){
                /*
                |-----------------------------------
                | Enable pretty exception handler and
                | do necessary configuration
                |-----------------------------------
                */
                $exception->enable($config)
                    ->setTitle()
                    ->setDebugger(Debugger::getBlueScreen())
                    ->setEmailAddress(
                        $config['params']['log_email'],
                        $config['enable_error_emailing']
                    )
                    ->run();
                unset($config);
            }
        );
    }
}
