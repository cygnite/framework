<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Foundation\Http;

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

    const CONTENT_TYPE = 'text/html';

    /**
     * The list of constants is complete according to the
     * Symfony HTTP Response class
     *
     * @link https://github.com/symfony/http-foundation/blob/master/Response.php
     */
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;            // RFC2518
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;          // RFC4918
    const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_IM_USED = 226;               // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_MISDIRECTED_REQUEST = 421;                                         // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_LOCKED = 423;                                                      // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
    const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

    /**
     * @var array
     */
    public static $httpStatus = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a Teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        428 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    );

    public $statusCode = 200;

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

    protected $contentType = 'text/html';

    protected $charset;

    /**
     * @param string $content
     * @param int    $statusCode
     * @param array  $headers
     */
    public function __construct($content = '', $statusCode = 200, $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($statusCode);

        /*
         | set all headers
         */
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    /**
     * <code>
     *
     *  Response::make($content, 200)->send();
     *
     *  Response::make($content, function ($response)
     *  {
     *      return $response->setHeader($key, $value)->send();
     *  });
     *
     * </code>
     *
     * @param string       $content
     * @param callable|int $statusCode
     * @param array        $headers
     * @return static
     */
    public static function make($content = '', $statusCode = 200, $headers = [])
    {
        // We will check if statusCode given as Closure object
        if ($statusCode instanceof \Closure) {
            return $statusCode(new static($content, 200, $headers));
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

        if (!isset(static::$httpStatus)) {
            throw new ResponseException(sprintf('Invalid status code provided %s', $statusCode));
        }

        if (!empty($message)) {
            $this->statusMessage = $message;

            return $this;
        }

        $this->statusMessage = self::$httpStatus[$statusCode];

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

        if (isset(static::$httpStatus[$this->getStatusCode()])) {
            $message = static::$httpStatus[$this->getStatusCode()];
        }

        header('Status: '.$this->getStatusCode().' '.$message);
    }

    /**
     * Set header for FCGI based servers
     */
    protected function setHeaderForServer()
    {
        header($this->protocol().' '.$this->getStatusCode().' '.static::$httpStatus[$this->getStatusCode()]);
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
