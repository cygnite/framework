<?php

namespace Cygnite\Exception\Http;

class HttpException extends \RuntimeException implements HttpExceptionInterface
{
    private $statusCode;

    private $headers;

    /**
     * @param string     $statusCode
     * @param null       $message
     * @param \Exception $previous
     * @param array      $headers
     * @param int        $code
     */
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int|string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
