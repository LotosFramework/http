<?php

namespace Lotos\Http\ServerRequest;

use Psr\Http\Message\{ServerRequestInterface, UriInterface, StreamInterface};
use Lotos\Http\Request\Request;
use Lotos\Http\Stream\PhpInputStream;
use Lotos\Http\Uri\UriHelperTrait;
use Lotos\Http\Message\MessageTrait;

class ServerRequest extends Request implements ServerRequestInterface
{
    use UriHelperTrait;
    use MessageTrait;

    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        string $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        $parsedBody = null,
        string $protocol = '1.1'
    ) {
        if ($body === 'php://input') {
            $body = new PhpInputStream();
        }
        $this->initialize($uri, $method, $body, $headers);
        $this->serverParams  = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->cookieParams  = $cookies;
        $this->queryParams   = $queryParams;
        $this->parsedBody    = $parsedBody;
        $this->protocol      = $protocol;
    }

    public function getServerParams() : array
    {
        return $this->serverParams;
    }

    public function getCookieParams() : array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies) : ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query) : ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    public function getUploadedFiles() : array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles) : ServerRequestInterface
    {
        $this->validateUploadedFiles($uploadedFiles);
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data) : ServerRequestInterface
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return (array_key_exists($name, $this->attributes))
            ? $this->attributes[$name]
            : $default;
    }

    public function withAttribute($name, $value) : ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$attribute] = $value;
        return $clone;
    }

    public function withoutAttribute($name) : ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }

    private function initialize(
        $uri = null,
        string $method = null,
        $body = 'php://memory',
        array $headers = []
    ) : void {
        if ($method !== null) {
            $this->setMethod($method);
        }
        parent::__construct($uri, $method, $body, $headers);
        $this->stream = $this->getStream($body, 'wb+');
        $this->setHeaders($headers);
        if (! $this->hasHeader('Host') && $this->uri->getHost()) {
            $this->headerNames['host'] = 'Host';
            $this->headers['Host'] = [$this->getHostFromUri()];
        }
    }
}
