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

/**
 * Class StreamedResponse
 *
 * @package Cygnite\Http\Responses
 */
class StreamedResponse extends Response
{
    protected $callback = null;

    protected $streamed = false;

    /**
     * Constructor of StreamedResponse class
     *
     * @param \Cygnite\Http\Responses\callable $callback
     * @param type $status
     * @param type $headers
     */
    public function __construct(callable $callback = null, $status = ResponseHeaders::HTTP_OK, $headers = [])
    {
        parent::__construct("", $status, $headers);

        if ($callback !== null) {
            $this->setCallback($callback);
        }
    }

    /**
     * Factory method to return StreamedResponse object to chain methods
     *
     * @param type $callback
     * @param type $status
     * @param type $headers
     * @return \static
     */
    public static function make($callback = null, $status = ResponseHeaders::HTTP_OK, $headers = [])
    {
        return new static($callback, $status, $headers);
    }

    /**
     * Send streamed contents
     *
     * @throws \LogicException
     */
    public function sendContent()
    {
        if (null === $this->callback) {
            throw new \LogicException('The SteamedResponse callback must not be null.');
        }

        if (!$this->streamed && $this->callback !== null) {
            call_user_func($this->callback);
            $this->streamed = true;
        }
    }

    /**
     * Throw exception if user tries to send content for
     * StreamedResponse.
     *
     * @param type $content
     * @throws \LogicException
     */
    public function setContent($content = null)
    {
        //you are not alowed to set content for a stream response
        if ($content !== null && $content !== "") {
            throw new \LogicException("Cannot set content in a stream response");
        }
    }

    /**
     * Returns false
     *
     * @return boolean
     */
    public function getContent()
    {
        return false;
    }

    /**
     * Set the callback for streaming response
     *
     * @param \Cygnite\Http\Responses\callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }
}
