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
 * Class Response
 *
 * @package Cygnite\Http\Responses
 */
class RedirectResponse extends Response
{
    /** @var string Redirect URL */
    protected $redirectUrl;

    public function __construct($targetUrl, $statusCode = ResponseHeader::HTTP_FOUND, array $headers = [])
    {
        parent::__construct("", $statusCode, $headers);

        $this->setRedirectToUrl($targetUrl);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $targetUrl
     */
    public function setRedirectToUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        $this->headers->set("Location", $this->redirectUrl);
    }
}