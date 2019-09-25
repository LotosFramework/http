<?php

namespace Lotos\Http\Strategies;

use Lotos\Http\{StrategyInterface, Message\HttpMessagesTrait};
use Psr\Http\Message\{ResponseInterface, StreamInterface};
use Fig\Http\Message\StatusCodeInterface;


class HtmlStrategy implements StrategyInterface, StatusCodeInterface
{
    use HttpMessagesTrait;

    private $response;
    private $responseBody;

    public function process(ResponseInterface $response) : void
    {
        $this->resolve($response->withHeader('Content-Type', 'text/html; charset=utf-8'));
    }

    private function resolve(ResponseInterface $response) : void
    {
        header(
            'HTTP/' . $response->getProtocolVersion() .
            ' ' . $response->getStatusCode() .
            ' ' . $response->getReasonPhrase()
        );
        foreach ($response->getHeaders() as $header => $values) {
            header($header . ':' . implode(',', $values));
        }
        printf($response->getBody()->getContents());
    }

}
