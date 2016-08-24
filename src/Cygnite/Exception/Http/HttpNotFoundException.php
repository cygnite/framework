<?php

namespace Cygnite\Exception\Http;

class HttpNotFoundException extends HttpException
{
    /**
     * @param null       $message
     * @param \Exception $previous
     * @param int        $code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
