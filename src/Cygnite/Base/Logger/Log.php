<?php
namespace Cygnite\Base\Logger;

use Config;
use Cygnite\Exception;

class Log
{
    public $log_errors = '';
    protected $logDateFormat =  'Y-m-d H:i:s';
    protected $fileName = null;
    protected $fp = null;
    protected $logPath;
    protected $logSize;
    protected $logExt = ".log";
    protected $config = [];

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

        $this->fileName  = ($this->config['log_file_name'] !="") ?
            $this->config['log_file_name']  :
            'cf_error_logs';
    }

    public function read()
    {
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

        /*
        if (file_exists($logFilePath)) {
            if (filesize($logFilePath) > $this->logSize) {
                    $this->fp = fopen($logFilePath, 'w') or die("can't open file file!");
                    fclose($this->fp);
                    unlink($logFilePath);
           }
        }
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
