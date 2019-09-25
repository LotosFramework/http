<?php

namespace Lotos\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Lotos\Http\Message\MessageTrait;

class Response implements ResponseInterface
{
    use MessageTrait;

    public function __construct(
        $body = 'php://memory',
        int $status = 200,
        array $headers = []) {
        $this->setStatusCode($status);
        $this->stream = $this->getStream($body, 'wb+');
        $this->setHeaders($headers);
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '') : ResponseInterface
    {
        $clone = clone $this;
        $clone->setStatusCode($code, $reasonPhrase);
        return $clone;
    }


    public function setStatusCode(int $code, string $reasonPhrase = '') : void
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getReasonPhrase() : string
    {
        return $this->reasonPhrase;
    }

}
