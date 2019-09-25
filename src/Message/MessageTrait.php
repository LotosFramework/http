<?php

namespace Lotos\Http\Message;

use Psr\Http\Message\{StreamInterface, MessageInterface};
use Lotos\Http\Message\Exception\MessageException;
use Lotos\Http\Stream\Stream;

trait MessageTrait {

    use MessageValidatorTrait;

    private $protocol = '1.1';
    private $headerNames = [];
    private $stream;

    public function getProtocolVersion() : string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version) : void
    {
        try {
            $this->ensureNotEmpty($version);
            $this->ensureNotString($version);
            $this->ensureMatchPattern($version);
        } catch(MessageException $e) {
            throw new MessageException($e->getMessage());
        }
    }

    protected function getStream($stream, string $modeIfNotInstance) : StreamInterface
    {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }
        if (! is_string($stream) && ! is_resource($stream)) {
            throw new Exception\InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }
        return new Stream($stream, $modeIfNotInstance);
    }


    public function getHeaders() : array
    {
        return $this->headers;
    }

    private function assertHeader($name) : void
    {
        HeaderSecurity::assertValidName($name);
    }

    protected function setHeaders(array $originalHeaders) : void
    {
        $headerNames = $headers = [];
        foreach ($originalHeaders as $header => $value) {
            $value = $this->filterHeaderValue($value);
            $this->assertHeader($header);
            $headerNames[strtolower($header)] = $header;
            $headers[$header] = $value;
        }
        $this->headerNames = $headerNames;
        $this->headers = $headers;
    }

    protected function filterHeaderValue($values) : array
    {
        if (! is_array($values)) {
            $values = [$values];
        }
        if ([] === $values) {
            throw new Exception\InvalidArgumentException(
                'Invalid header value: must be a string or array of strings; '
                . 'cannot be an empty array'
            );
        }
        return array_map(function ($value) {
            HeaderSecurity::assertValid($value);
            return (string) $value;
        }, array_values($values));
    }


    public function hasHeader($name) : bool
    {
        return !empty($this->headerNames[strtolower($name)]);
    }

    public function getHeader($name) : array
    {
        try {
            $header = $this->headerNames[strtolower($name)];
            return $this->headers[$header];
        } catch(MessageException $e) {
            return [];
        }
    }

    public function getHeaderLine($name) : string
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader($name, $value) : MessageInterface
    {
        $this->assertHeader($name);
        $lowerCased = strtolower($name);

        $clone = clone $this;
        if($clone->hasHeader($name)) {
            unset($clone->headers[$clone->headerNames[$lowerCased]]);
        }

        $value = $this->filterHeaderValue($value);

        $clone->headerNames[$lowerCased] = $name;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function withAddedHeader($name, $value) : MessageInterface
    {
        $this->assertHeader($name);

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $header = $this->headerNames[strtolower($name)];

        $clone = clone $this;
        $value = $this->filterHeaderValue($value);
        $clone->headers[$header] = array_merge($this->headers[$header], $value);
        return $clone;
    }

    public function withoutHeader($name) : MessageInterface
    {
        if (!$this->hasHeader($header)) {
            return clone $this;
        }

        $lowerCased = strtolower($header);
        $original   = $this->headerNames[$lowerCased];

        $clone = clone $this;
        unset($clone->headers[$original], $clone->headerNames[$lowerCased]);
        return $clone;
    }

    public function getBody() : StreamInterface
    {
        return $this->stream;
    }

    public function withBody(StreamInterface $body) : MessageInterface
    {
        $clone = clone $this;
        $clone->stream = $body;
        return $clone;
    }
}
