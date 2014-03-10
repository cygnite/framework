<?php 
namespace Cygnite;

use Exception as BaseException;

//use Cygnite\Helpers\Config;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
*
* // Cygnite Framework
*
* An open source application development framework for PHP 5.3x or newer
*
* License
*
* This source file is subject to the MIT license that is bundled
* with this package in the file LICENSE.txt.
* http://www.cygniteframework.com/license.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to sanjoy@hotmail.com so I can send you a copy immediately.
*
* @package              :  Packages
* @sub packages         :  Library
* @filename             :  Exceptions
* @description          :  This library used to handle all errors or
*                          exceptions of Cygnite Framework.
* @author               :  Cygnite Dev Team
* @Copyright            :  Copyright (c) 2013 - 2014,
* @link	               :  http://www.cygniteframework.com
* @since	               :  Version 1.0
* @file Source
* @warning              :  Any changes in this library can cause abnormal
*                          behaviour of the framework
*
*
*/

/* If you need to log errors please use set logger
$logConfig = Config::getConfig('error_config', 'log_errors');

if ($logConfig == 'on') {
    // Load the Logger Library.
    Cygnite::import('packages.cygnite.base.logger');
}
*/


   /**
    * Class Exceptions
    *
    * @package Cygnite\Libraries
    *
    */

class Exceptions extends BaseException
{

    /**
     * @var bool
     */
    private $_debug = false;

    private $_obLevel;

    /**
     * Constructor Function
     *
     * @access public
     */

    public function __construct()
    {
        //$this->_obLevel = ob_get_level();

        // Use our custom handler.
        /*
         call_user_func(array(
                $this,
                'handleExceptions',
            ));
         set_error_handler(
            array(
             $this,
             'handleExceptions',
            )
        );*/
        set_error_handler(
            array(
                $this,
                'handleExceptions',
            )
        );
    }//end __construct()

    public function handle($exception = null)
    {
        /*
        $whoops = new \Whoops\Run();

        //Configure the PrettyPageHandler:
        $errorPage = new \Whoops\Handler\PrettyPageHandler();

        $errorPage->setPageTitle("It's broken!"); // Set the page's title
        $errorPage->setEditor("sublime");         // Set the editor used for the "Open" link
        /*$errorPage->addDataTable(
            "Extra Info",
            array(
                "stuff"     => 123,
                "foo"       => "bar",
                "useful-id" => "baloney"
            )
        );*/
        /*
        $whoops->pushHandler($errorPage);
        $whoops->register(); */

    }

    /**
     * @false        $errType
     * @false string $errHeader
     * @false        $errMessage
     * @false null   $errFile
     * @false null   $line
     * @false null   $debug
     *
     */
    public function handleExceptions(
        $errType,
        $errHeader,
        $errMessage,
        $errFile = null,
        $line = null,
        $debug = null
    ) {
        $errHeader = 'Error Encountered';

        $this->_debug = $debug;

        switch ($errType) {
            case E_USER_ERROR:

                switch ($this->_debug){
                    case 'false':
                        $this->_error(
                            $errHeader,
                            $line,
                            $errMessage,
                            $errFile,
                            $this->_debug,
                            ''
                        );
                        break;
                    case 'true':
                        $this->_error(
                            $errHeader,
                            $line,
                            $errMessage,
                            $errFile,
                            $this->_debug,
                            'fatal'
                        );
                        break;
                }

                break;
            case E_USER_WARNING:
                $this->_error($errHeader, $line, $errMessage, $errFile, '', 'warning');
                break;
            case E_USER_NOTICE:
                $this->_error($errHeader, $line, $errMessage, $errFile, '', 'notice');
                break;
        }
    }

    /**
     * @param        $title
     * @param        $line
     * @param        $errMessage
     * @param        $errFile
     * @param string $debug
     * @param null   $type
     */
    private function _error($title,$line,$errMessage,$errFile,$debug = "",$type = null)
    {
        ob_start();
        $arr =  array(
                  'title' => $title,
                  'line' =>$line,
                  'message'=>$errMessage,
                  'type' =>$type,
                  'debug'=>$debug,
                  'file' =>$errFile
              );

        @extract($arr);
        include APPPATH.DS.'errors'.DS.'error'.EXT;

        $output= ob_get_contents();
            ob_get_clean();
            echo $output;
            ob_end_flush();
            //ob_get_flush();
        echo ($debug === true || $debug === 1) ? '' : $debug;
        echo ($type === 'fatal') ?  exit : '';
    }

    /**
     * @param array $err_config
     */
    public function setEnvironment($err_config = array())
    {
        /** Check if environment is development and display errors **/
        switch (MODE) {
            case 'development':
                error_reporting(-1);
                /* error_reporting($err_config['level']);
                   ini_set('display_errors',$err_config['display_errors']);
                   ini_set('log_errors', $err_config['log_errors']);
                   ini_set('error_log', ROOTDIR.DS.str_replace("/", "", APPPATH).DS.'tmp'.DS.'logs'.DS.'error.log');
                */
                break;
            case 'production':
                error_reporting($err_config['level']);
                ini_set('display_errors', $err_config['display_errors']);
                ini_set('log_errors', $err_config['log_errors']);
                ini_set('error_log', ROOTDIR.DS.APPPATH.DS.'tmp'.DS.'logs'.DS.'error.log');
                break;
        }

    }


    public function setLog()
    {

    }

    /**
     * @param $message
     * @param $level
     */
    public function triggerUserError($message, $level)
    {

    }
 }
