<?php
namespace Cygnite\Exception;

use Closure;
use Exception;
use Tracy\Helpers;
use Tracy\Debugger;
use Cygnite\Helpers\Config;
use Cygnite\Foundation\Application;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class Handler
 * This file is used to define all necessary configurations for
 * Tracy debugger component to handle exceptions or to debug app
 * @package Cygnite\Exception
 */
class ExceptionHandler implements ExceptionInterface
{
    const DEBUG = Debugger::DEBUG;
    
    const INFO = Debugger::INFO;
    
    const WARNING = Debugger::WARNING;
    
    const ERROR = Debugger::ERROR;
    
    const EXCEPTION = Debugger::EXCEPTION;
    
    const CRITICAL = Debugger::CRITICAL;
    
    protected $name = 'Cygnite Framework';

    private static $style = 'pretty';

    protected $debugger;

    protected $enableMode;

    protected $enableLogger = false;

    protected $logPath;

    protected $env;

    /**
     * @param $enableLogger
     * @param $loggerDir
     * @return $this
     */
    public function enable($enableLogger, $loggerDir)
    {
        $this->setLogConfig($enableLogger, $loggerDir);

        $debuggerMode = null;
        $debuggerMode = Debugger::DEVELOPMENT;

        if ($this->env == 'production') {
            $debuggerMode = Debugger::PRODUCTION;
        }

        return $this->enableDebuggerWith($debuggerMode);
    }

    /**
     * @param $enableMode
     * @return $this
     */
    private function enableDebuggerWith($enableMode)
    {
        if ($this->isLoggerEnabled() == true) {
            Debugger::enable($enableMode, $this->getLogPath());
        } else {
            Debugger::enable($enableMode);
        }

        return $this;
    }

    /**
     * Set environment
     *
     * @param $env
     * @return $this
     */
    public function setEnv($env)
    {
        $this->env = $env;

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

    /**
     * Log errors
     *
     * @param $e
     */
    public function log($e)
    {
        Debugger::log($e);
    }

    /**
     * @param $enableLogger
     * @param $logPath
     * @return $this
     */
    public function setLogConfig($enableLogger, $logPath)
    {
        $this->enableLogger = $enableLogger;
        $this->logPath = CYGNITE_BASE.DS.toPath($logPath).DS;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLoggerEnabled()
    {
        return $this->enableLogger;
    }

    /**
     * @return mixed
     */
    public function getLogPath()
    {
        return (isset($this->logPath) ? $this->logPath : null);
    }

    /**
     * Set Title
     *
     * @return $this
     */
    public function setTitle()
    {
        $this->debugger->info[] = $this->name.Application::version();

        return $this;
    }

    /**
     * Set email address for sending error report
     *
     * @param      $email
     * @param bool $errorEmailing
     * @return $this
     */
    public function setEmailAddress($email, $errorEmailing = false)
    {
        if ($errorEmailing) {
            Debugger::$email = $email;
        }

        return $this;
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
     * Get the self instance of handler
     *
     * @param callable $callback
     * @return mixed
     */
    public static function register(Closure $callback)
    {
        if ($callback instanceof Closure) {
            return $callback(new HandlerException);
        }
    }

    /**
     * Run the debugger to handle all exception and
     * display stack trace
     *
     *
     */
    public function run()
    {
        $handler = $this;
        $this->setCollapsePaths();

        //Add new panel to debug bar
        $this->addPanel(function ($e) use ($handler) {
            if (!is_null($path = $handler->assetsPath())) {
                $contents = $handler->includeAssets($path);
            }

            if (!is_null($e)) {
                if ($handler->isLoggerEnabled()) {
                    $handler->log($e);
                }

                return [
                    'tab' => $handler->name,
                    'panel' => '<h1>
                <p class="heading-blue">
                    <a href="http://www.cygniteframework.com">'.$handler->name.' </a>
                </p>
                    </h1>
                    <p> Version : '.Application::version().' </p>
                    <style type="text/css" class="tracy-debug">'.$contents.'</style>'
                ];
            }
        });

        $this->addPanel(function ($e) {
            if (!$e instanceof \PDOException) {
                return;
            }
            if (isset($e->queryString)) {
                $sql = $e->queryString;
            }

            return isset($sql) ? ['tab' => 'SQL', 'panel' => $sql] : null;
        });
    }

    /**
     * Add panel to debugger
     *
     * @param $callback
     * @return $this
     */
    public function addPanel($callback)
    {
        $this->getTracyDebugger()->addPanel($callback);

        return $this;
    }

    /**
     * Add Sql Panel to debugger
     *
     * @param $callback
     */
    public function addSqlPanel($callback)
    {
        $this->getTracyDebugger()->addSqlPanel($callback);
    }

    /**
     * Get Tracy Debugbar instance
     *
     * @return \Tracy\Bar
     */
    public function getBar()
    {
        return Debugger::getBar();
    }

    /**
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function includeAssets($path)
    {
        return file_get_contents($path.self::$style.'.css');
    }

    /**
     * Get the asset path
     *
     * @return string
     */
    public function assetsPath()
    {
        if (is_dir($path = $this->getAssetsPath())) {
            return $path;
        }
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
        /*
        | Exception handler registered here. So it will handle all
        | exceptions and thrown as pretty format
        |-----------------------------------
        | Enable pretty exception handler and
        | do necessary configuration
        |-----------------------------------
        */
        $config = Config::get('global.config');

        $this->enable($config['logs']['activate'], $config['logs']['path'])
            ->setTitle()
            ->setDebugger(Debugger::getBlueScreen())
            ->setEmailAddress(
                $config['logs']['email'],
                $config['logs']['error.emailing']
            )->run();
    }

    /**
     * @param $data
     * @param $type
     */
    public function dump($data, $type)
    {
        Debugger::barDump($data, $type);
    }

    /**
     * We will display custom error page in production mode
     *
     * @param null $e
     * @throws \Exception
     */
    public function importCustomErrorPage($e = null)
    {
        $path = CYGNITE_BASE.DS.toPath(APPPATH.'/Views/errors/');
        if ($e == null) {
            Debugger::$errorTemplate = include $path.'500.view'.EXT;
        }

        $statusCode = 500;
        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
        } else {
            if (method_exists($e, 'getCode')) {
                $statusCode = $e->getCode();
            }
        }

        if ($statusCode == 0) {
            $statusCode = 500;
        }

        if (file_exists($path.$statusCode.'.view'.EXT)) {
            $error = ['error.code' => $statusCode, 'message' => $e->getMessage()];
            extract($error);
            Debugger::$errorTemplate = include $path.$statusCode.'.view'.EXT;
        } else {
            throw new \Exception("Error view file not exists ".$path.$e->getStatusCode().'.view'.EXT);
        }
    }

    /**
     * @param $e
     * @return mixed
     */
    public function renderErrorPage($e)
    {
        $this->importCustomErrorPage($e);
    }
}
