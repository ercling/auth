<?php
namespace base;

use base\helpers\HtmlPurifier;

class HtmlResponseFormatter implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'text/html';

    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if ($response->data !== null) {
            $response->content = $response->data;
        }
    }
}