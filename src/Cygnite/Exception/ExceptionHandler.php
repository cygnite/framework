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

    public $name = 'Cygnite Framework';

    private static $style = 'pretty';

    protected $debugger;

    protected $enableMode;

    public $enableLogger = false;

    public $logPath;

    /**
     * @param $configurations
     * @return $this
     */
    public function enable($enableLogger, $loggerDir)
    {
        if (ENV == 'development') {
            $this->enableDebuggerWith(Debugger::DEVELOPMENT, $loggerDir);
        } else {
            $this->setLogConfig($enableLogger, $loggerDir)
                 ->enableDebuggerWith(Debugger::PRODUCTION, $loggerDir);
        }

        return $this;
    }

    /**
     * @param       $enableMode
     * @param array $config
     */
    private function enableDebuggerWith($enableMode, $loggerDir = "")
    {
        if ($this->isLoggerEnabled() == true) {
            $logPath = str_replace('.', '/', $loggerDir).'/';
            Debugger::enable($enableMode, $logPath);
        } else {
            Debugger::enable($enableMode);
        }
    }

    public function setLogConfig($enableLogger, $logPath)
    {
        $this->enableLogger = $enableLogger;
        $this->logPath = $logPath;

        return $this;
    }

    public function isLoggerEnabled()
    {
        return $this->enableLogger;
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
    public function setEmailAddress($email, $isSetEmail = false)
    {
        if ($isSetEmail == true) {
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

    /**
     * Get the self instance of handler
     *
     * @param callable $callback
     * @return mixed
     */
    public static function register(Closure $callback)
    {
        if ($callback instanceof Closure) {
            return $callback(new Handler);
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
       $this->addPanel(
            function($e) use($handler) {

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
                        <span class="heading-blue">
                        <a href="http://www.cygniteframework.com">'.$handler->name.' </a>
                        </span>
                        </h1>
                        <p> Version : '.Application::version().' </p>
                        <style type="text/css" class="tracy-debug">'.$contents.'</style>'
                    ];
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

                return isset($sql) ? ['tab' => 'SQL', 'panel' => $sql] : NULL;

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

    /**
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function includeAssets($path)
    {
        //$this->overwriteBlueScreenStyle($path);

        return file_get_contents($path.self::$style.'.css');
    }

    private function overwriteBlueScreenStyle($path)
    {
        $vendor = CYGNITE_BASE.DS.'vendor';
        $stylePath = Config::get('global.config', 'bluescreen.style');
        $screenPath = $vendor.DS.str_replace('.', DS, $stylePath).DS."bluescreen.css";

        if (!file_exists($screenPath)) {
            throw new Exception("Tracy debugger bluescreen.css not found inside vendor directory");
        }
        $blueScreen = file_get_contents($screenPath);
        $pretty = file_get_contents($path.self::$style.'.css');
        $blueScreen = (string) $blueScreen."\n".$pretty;
        if (!string_has($blueScreen, '@Author:Cygnite')) {
            file_put_contents($blueScreen);
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
        /*
        | Exception handler registered here. So it will handle all
        | exceptions and thrown as pretty format
        |-----------------------------------
        | Enable pretty exception handler and
        | do necessary configuration
        |-----------------------------------
        */
        $config = Config::get('global.config');
        $this->enable($config['enable_logging'], $config['log_path'])
            ->setTitle()
            ->setDebugger(Debugger::getBlueScreen())
            ->setEmailAddress(
                $config['params']['log_email'],
                $config['enable_error_emailing']
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
     * @param $e
     * @return mixed
     */
    public function importCustomErrorPage($e = null)
    {
        $path = CYGNITE_BASE.DS.str_replace('/', DS, APPPATH.'/Views/errors/');
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
            $error = ['error.code' => $e->getStatusCode(), 'message' => $e->getMessage()];
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
