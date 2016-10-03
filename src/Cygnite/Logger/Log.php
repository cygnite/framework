<?php
namespace Cygnite\Logger;

use Closure;
use RuntimeException;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Class Log.
 * @package Cygnite\Logger
 */
class Log implements PsrLoggerInterface
{
    /**
     * The Monolog logger instance.
     *
     * @var \Monolog\Logger
     */
    protected $monolog;

    /**
     * The Log types.
     *
     * @var array
     */
    protected $types = [
        'debug'     => Monolog::DEBUG,
        'info'      => Monolog::INFO,
        'notice'    => Monolog::NOTICE,
        'warning'   => Monolog::WARNING,
        'error'     => Monolog::ERROR,
        'critical'  => Monolog::CRITICAL,
        'alert'     => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];

    /**
     * Log constructor.
     *
     * @param Monolog $monolog
     */
    public function __construct(Monolog $monolog)
    {
        $this->monolog = $monolog;
    }

    /**
     * Write a infomation message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function info($message, array $context = [])
    {
        return $this->write('info', $message, $context);
    }

    /**
     * Write a notice message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function notice($message, array $context = [])
    {
        return $this->write('notice', $message, $context);
    }

    /**
     * Write a alert message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function alert($message, array $context = [])
    {
        return $this->write('alert', $message, $context);
    }

    /**
     * Write a warning message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function warning($message, array $context = [])
    {
        return $this->write('warning', $message, $context);
    }

    /**
     * Write a debug message to logs.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function debug($message, array $context = [])
    {
        return $this->write('debug', $message, $context);
    }

    /**
     * Write a critical message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function critical($message, array $context = [])
    {
        return $this->write('critical', $message, $context);
    }

    /**
     * Write an emergency message to log.
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function emergency($message, array $context = [])
    {
        return $this->write('emergency', $message, $context);
    }

    /**
     * Write a error message to log.
     *
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function error($message, array $context = [])
    {
        return $this->write('error', $message, $context);
    }

    /**
     * Write a log message to file.
     *
     * @param mixed $type
     * @param string $message
     * @param array $context
     */
    public function log($type, $message, array $context = [])
    {
        return $this->write($type, $message, $context);
    }

    /**
     * Write log.
     *
     * @param $type
     * @param $message
     * @param $context
     */
    protected function write($type, $message, $context)
    {
        $this->monolog->{$type}($message, $context);
    }

    /**
     * Register a file log handler.
     *
     * @param $path
     * @param string $type
     */
    public function file($path, $type = 'debug')
    {
        $this->pushHandler(new StreamHandler($path, $this->getLevel($type)));
    }

    /**
     * Register a daily file log handler.
     *
     * @param $path
     * @param int $days
     * @param string $type
     */
    public function dailyFileLog($path, $days = 0, $type = 'debug')
    {
        $this->pushHandler(new RotatingFileHandler($path, $days, $this->getLevel($type)));
    }

    /**
     * Register a Error Log Handler.
     *
     * @param string $type
     * @param int $messageType
     */
    public function errorLog($type = 'debug', $messageType = ErrorLogHandler::OPERATING_SYSTEM)
    {
        $this->pushHandler(new ErrorLogHandler($messageType, $this->getLevel($type)));
    }

    /**
     * Register a Syslog handler to Monolog Library.
     *
     * @param string $name
     * @param string $type
     * @return Log
     */
    public function sysLog($name = 'cygnite', $type = 'debug')
    {
        return $this->pushHandler(new SyslogHandler($name, LOG_USER, $type), false);
    }

    /**
     * Push Handler to Monolog.
     *
     * @param $handler
     * @param bool $formatter
     * @return $this
     */
    protected function pushHandler($handler, $formatter = true)
    {
        if ($formatter == false) {
            return $this->monolog->pushHandler($handler);
        }

        $this->monolog->pushHandler($handler);
        $handler->setFormatter($this->getDefaultFormatter());

        return $this;
    }

    /**
     * Get the Log Level.
     *
     * @param $type
     * @return mixed
     */
    protected function getLevel($type)
    {
        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException('Invalid log type.');
        }

        return $this->types[$type];
    }

    /**
     * @return LineFormatter
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter(null, null, true, true);
    }

    /**
     * Add info to message.
     *
     * @param $message
     * @param $context
     * @return bool
     */
    public function addInfo($message, $context)
    {
        return $this->monolog->addInfo($message, $context);
    }

    /**
     * Push a processor callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function pushProcessor(callable $callback)
    {
        return $this->monolog->pushProcessor($callback);
    }

    /**
     * Get Monolog instance.
     *
     * @return Monolog
     */
    public function getMonolog()
    {
        return $this->monolog;
    }
}
