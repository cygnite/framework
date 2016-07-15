<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Http\Responses;

use Cygnite\Http\Responses\ResponseHeader;
use Cygnite\Exception\Http\ResponseException;

/**
 * Class Response
 *
 * @package Cygnite\Foundation\Http
 */

class Response implements ResponseInterface
{
    const PROTOCOL = 'HTTP/1.1';

    const CHARSET = 'UTF-8';

    const CONTENT_TYPE = ResponseHeader::CONTENT_TYPE_HTML;

    public $statusCode = ResponseHeader::HTTP_OK;

    protected $statusMessage;

    /**
     * @var  array  An array of headers
     *
     * @since  2.0
     */
    protected $headers = [];

    /**
     * @var  string  The content of the response
     *
     * @since  2.0
     */
    protected $content;

    protected $contentType = self::CONTENT_TYPE;

    protected $charset;

    /**
     * @param string $content
     * @param int    $statusCode
     * @param array  $headers
     */
    public function __construct($content = '', $statusCode = ResponseHeader::HTTP_OK, $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($statusCode);

        if (!empty($headers)) {
            /*
             | set all headers
             */
            foreach ($headers as $key => $value) {
                $this->setHeader($key, $value);
            }
       }

    }

    /**
     * Returns Response object
     *
     * <code>
     *  Response::make($content, ResponseHeader::HTTP_OK)->send();
     *
     *  Response::make($content, function ($response)
     *  {
     *      return $response->setHeader($key, $value)->send();
     *  });
     * </code>
     *
     * @param string       $content
     * @param callable|int $statusCode
     * @param array        $headers
     * @return static
     */
    public static function make($content = '', $statusCode = ResponseHeader::HTTP_OK, $headers = [])
    {
        // We will check if statusCode given as Closure object
        if ($statusCode instanceof \Closure) {
            return $statusCode(new static($content, ResponseHeader::HTTP_OK, $headers));
        }

        return new static($content, $statusCode, $headers);
    }

    /**
     * @param $content
     * @return $this
     * @throws \Cygnite\Exception\Http\ResponseException
     */
    public function setContent($content = null)
    {
        if (!is_string($content)) {
            throw new ResponseException('Response content must be a string.');
        }

        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode = 200)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string $message
     * @return $this
     * @throws \Cygnite\Exception\Http\ResponseException
     */
    public function statusMessage($message = '')
    {
        $statusCode = $this->getStatusCode();

        if (!isset(ResponseHeader::$httpStatus[$statusCode])) {
            throw new ResponseException(sprintf('Invalid status code provided %s', $statusCode));
        }

        if (!empty($message)) {
            $this->statusMessage = $message;

            return $this;
        }

        $this->statusMessage = ResponseHeader::$httpStatus[$statusCode];

        return $this;
    }

    /**
     * @param      $name
     * @param      $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader($name, $value, $replace = true)
    {
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = array($value);
        } else {
            array_push($this->headers[$name], $value);
        }

        return $this;
    }

    /**
     * @param $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        $this->setHeader('Content-Type', $contentType . '; charset=' . $this->getCharset());

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return (isset($this->contentType) ? $this->contentType : self::CONTENT_TYPE);
    }

    /**
     * @param $charset
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        $this->setContentType($this->getContentType());

        return $this;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return (isset($this->charset) ? $this->charset : self::CHARSET);
    }

    public function setAsNotModified()
    {
        $this->setStatusCode(304)->setContent(null);

        $headersToRemove = [
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified'
        ];

        foreach ($headersToRemove as $header) {
            // @todo remove header key
        }

        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function server($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : false;
    }

    /**
     * @return $this
     * @throws \Cygnite\Exception\Http\ResponseException
     */
    public function sendHeaders()
    {
        // Throw exception if header already sent
        if (headers_sent()) {
            throw new ResponseException('Cannot send headers, headers already sent.');
        }

        /*
         | If headers not set already we will set header here
         */
        if (!isset($this->headers['Content-Type'])) {
            $this->setHeader('Content-Type', $this->getContentType().'; charset='.$this->getCharset());
        }

        // Check if script is running in FCGI server
        if ($this->server('FCGI_SERVER_VERSION')) {
            /*
             | We will set Header for Fast-CGI server
             */
            $this->setFastCgiHeader();
        } else {
            $this->setHeaderForServer();
        }

        foreach ($this->headers as $name => $values) {

            foreach ($values as $value) {
                // Create the header and send it
                is_string($name) && $value = "{$name}: {$value}";
                header($value, true);
            }
        }

        return $this;
    }

    /**
     * We will set header for server script
     */
    protected function setFastCgiHeader()
    {
        $message = 'Unknown Status';

        if (isset(ResponseHeader::$httpStatus[$this->getStatusCode()])) {
            $message = ResponseHeader::$httpStatus[$this->getStatusCode()];
        }

        header('Status: '.$this->getStatusCode().' '.$message);
    }

    /**
     * Set header for FCGI based servers
     */
    protected function setHeaderForServer()
    {
        header($this->protocol().' '.$this->getStatusCode().' '.ResponseHeader::$httpStatus[$this->getStatusCode()]);
    }

    /**
     * @return string
     */
    private function protocol()
    {
        return ($this->server('SERVER_PROTOCOL')) ? $this->server('SERVER_PROTOCOL') : self::PROTOCOL;
    }

    /**
     * @return $this
     */
    public function sendContent()
    {
        echo $this->getContent();

        return $this;
    }

    /**
     * Send header and content to the browser
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders()->sendContent();

        // close the request
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (strtolower(PHP_SAPI) != 'cli') {
            ob_end_flush();
        }

        return $this;
    }
}
