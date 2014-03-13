<?php
namespace Cygnite\Base;

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
 * @Package          :  Packages
 * @Sub Packages     :  Base
 * @Filename         :  CF_Logger
 * @Description      :  This class is used to handle error logs of the cygnite framework
 * @Author           :  Sanjoy Dey
 * @Copyright        :  Copyright (c) 2013 - 2014,
 * @Link	         :  http://www.cygniteframework.com
 * @Since	         :  Version 1.0
 * @Filesource
 * @Warning          :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

//AppLogger::writeLog('Logger Initialized By Sanjay',__FILE__);

class Logger
{
    public $log_errors = '';
    protected $logDateFormat =  'Y-m-d H:i:s';
    protected $fileName = null;
    protected $fp = null;
    protected $logPath;
    protected $logSize;
    protected $logExt = ".log";
    protected $config = array();

    private function getLogConfig()
    {
        if (empty($this->config)) {
            $this->config =  Config::get('error_config');
        }

        if ($this->config['log_path'] !="" || $this->config['log_path'] !== null) {
            $this->log_path  = APPPATH.$this->config['log_path'].'/';
        } else {
            $this->log_path  = APPPATH.'temp/logs/';
        }
        // var_dump($this->config['ERROR_CONFIG']['log_file_name']);
        $this->fileName  = ($this->config['log_file_name'] !="") ?
            $this->config['log_file_name']  :
            'cf_error_logs';
    }

    public function read()
    {
        //var_dump( $this->config);
    }

    private function open($logFilePath)
    {
        $this->fp = fopen($logFilePath, 'a')
        or exit("Can't open log file ".$this->fileName.$this->logExt."!");
    }

    public function writeLog(
        $log_msg = "",
        $fileName,
        $logLevel = "log_debug",
        $log_size = 1
    ) {
        $this->getLogConfig();

         $logFilePath = $this->log_path.$this->fileName.'_'.date('Y-m-d').''.$this->logExt; //exit;
        $this->logSize = $log_size *(1024*1024); // Megs to bytes

        if ($this->config['log_trace_type'] == 2) {
            $this->writeInternal(
                $log_msg = "",
                $fileName,
                $logLevel = "log_debug",
                $log_size = 1
            );
            return true;
        } else {
            throw new Exception(
                "Log config not set properly in config file. Set log_errors = on and log_trace_type = 2 "
            );
        }

    }

    private function writeInternal(
        $logMsg = "",
        $fileName,
        $logLevel = "log_debug",
        $log_size = 1
    ) {

        if (!is_resource($this->fp)) {
            $this->open($this->logPath);
        }

        /*   if (file_exists($logFilePath)):
            if (filesize($logFilePath) > $this->logSize):
                    $this->fp = fopen($logFilePath, 'w') or die("can't open file file!");
                    fclose($this->fp);
                    unlink($logFilePath);
           endif;
        endif;
        */

        switch ($logLevel) {
            case 'log_debug':
                $logLevel = "LOG DEBUG :";
                break;
            case 'log_info':
                $logLevel = "LOG INFO : ";
                break;
            case 'log_warning':
                $logLevel = "LOG WARNING : ";
                break;
        }

        $logMsg = $logLevel."  [".date('Y-m-d H:i:s')."] -> [File:  $fileName ] ->  $logMsg".PHP_EOL;// write current time, file name and log msg to the log file

        flock($this->fp, LOCK_EX);
        fwrite($this->fp, $logMsg);
        flock($this->fp, LOCK_UN);
        fclose($this->fp);

        @chmod($this->logPath, FILE_WRITE_MODE);
        return true;
    }
}