<?php

namespace Cygnite\Http\Responses;

/**
 * Class JsonResponse.
 */
class JsonResponse extends Response
{
    // 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    protected $options = 15;

    /**
     * @param null  $content
     * @param array $headers
     * @param bool  $prettyPrint
     */
    public function __construct($content = null, $headers = [], $prettyPrint = false)
    {
        if (null === $content) {
            $content = new \ArrayObject();
        }

        if (is_array($content) || is_object($content)) {
            $content = $this->jsonEncode($content, $prettyPrint);
        }

        parent::__construct($content, ResponseHeader::HTTP_OK, $headers);
        $this->setContentType(ResponseHeader::CONTENT_TYPE_JSON);
    }

    /**
     * @param $data
     * @param $prettyPrint
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @return string
     * @reference https://github.com/symfony/HttpFoundation/blob/master/JsonResponse.php
     */
    public function jsonEncode($data, $prettyPrint)
    {
        try {
            $data = json_encode($data, (($prettyPrint) ? JSON_PRETTY_PRINT : 15));
        } catch (\Exception $e) {
            if ('Exception' === get_class($e) && 0 === strpos($e->getMessage(), 'Failed calling ')) {
                throw $e->getPrevious() ?: $e;
            }

            throw $e;
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        return $data;
    }

    /**
     * @param null  $data
     * @param array $headers
     *
     * @return static
     */
    public static function sendJson($data = null, $headers = [])
    {
        $response = new static($data, $headers);
        $response->send();

        return $response;
    }
}
