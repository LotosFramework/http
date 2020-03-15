<?php

namespace Lotos\Http\Request;

use Psr\Http\Message\{RequestInterface, UriInterface};
use Fig\Http\Message\RequestMethodInterface;
use Lotos\Http\{
    Message\MessageTrait,
    Uri\UriHelperTrait,
    Uri\Uri
};

class Request implements RequestInterface, RequestMethodInterface
{

    use MessageTrait;
    use UriHelperTrait;

    private $method = self::METHOD_GET;
    private $requestTarget;
    private $uri;

    protected $attributes = [];

    public function __construct(
        ?UriInterface $uri = null,
        string $method = null,
        string $body = 'php://temp',
        array $headers = []
    ) {
        $this->uri = $this->getUriInstance($uri);
    }

    public function getRequestTarget() : string
    {
        if(!is_null($this->requestTarget)) {
            return $this->requestTarget;
        }
        $target = $this->uri->getPath();
        if($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if(empty($target)) {
            $target = '/';
        }

        return $target;
    }

    public function withRequestTarget($requestTarget) : RequestInterface
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function setMethod(string $method) : void
    {
        $this->method = $method;
    }

    public function withMethod($method) : RequestInterface
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function getUri() : UriInterface
    {
        return $this->uri;
    }

    public function createUri($uri) : UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }
        if (is_string($uri)) {
            return new Uri($uri);
        }
        if ($uri === null) {
            return new Uri();
        }
        throw new Exception\InvalidArgumentException(
            'Invalid URI provided; must be null, a string, or a Psr\Http\Message\UriInterface instance'
        );
    }

    public function withUri(UriInterface $uri, $preserverHost = false) : RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;
        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }
        if (! $uri->getHost()) {
            return $new;
        }
        $host = $uri->getHost();
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }
        $new->headerNames['host'] = 'Host';

        foreach (array_keys($new->headers) as $header) {
            if (strtolower($header) === 'host') {
                unset($new->headers[$header]);
            }
        }
        $new->headers['Host'] = [$host];
        return $new;
    }

    public function toArray() : array
    {
        return $this->queryParams;
    }

    public function __call($method, $args)
    {
        if(substr($method, 0, 3) == 'get') {
            $var = substr(strtolower($method), 3);
            return $this->getUri()->getVars()[$var];
        }
    }
}
