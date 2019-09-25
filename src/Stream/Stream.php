<?php

namespace Lotos\Http\Stream;

use Psr\Http\Message\StreamInterface;
use Lotos\Http\Message\Exception\UnwritableStreamException;

class Stream implements StreamInterface {

    protected $resource;
    protected $stream;

    public function __construct($stream, string $mode = 'r')
    {
        $this->setStream($stream, $mode);
    }

    public function __toString() : string
    {
        if (! $this->isReadable()) {
            return '';
        }
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    public function close() : void
    {
        if (! $this->resource) {
            return;
        }
        $resource = $this->detach();
        fclose($resource);
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function getSize() : ?int
    {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        if ($stats !== false) {
            return $stats['size'];
        }

        return null;
    }

    public function tell() : int
    {
        if (! $this->resource) {
            throw Exception\UntellableStreamException::dueToMissingResource();
        }
        $result = ftell($this->resource);
        if (! is_int($result)) {
            throw Exception\UntellableStreamException::dueToPhpError();
        }
        return $result;
    }

    public function eof() : bool
    {
        if (! $this->resource) {
            return true;
        }
        return feof($this->resource);
    }

    public function isSeekable() : bool
    {
        if (! $this->resource) {
            return false;
        }
        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }

    public function seek($offset, $whence = SEEK_SET) : void
    {
        if (! $this->resource) {
            throw Exception\UnseekableStreamException::dueToMissingResource();
        }
        if (! $this->isSeekable()) {
            throw Exception\UnseekableStreamException::dueToConfiguration();
        }
        $result = fseek($this->resource, $offset, $whence);
        if (0 !== $result) {
            throw Exception\UnseekableStreamException::dueToPhpError();
        }
    }

    public function rewind() : void
    {
        $this->seek(0);
    }

    public function isWritable() : bool
    {
        if (! $this->resource) {
            return false;
        }
        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];
        return (
            strstr($mode, 'x')
            || strstr($mode, 'w')
            || strstr($mode, 'c')
            || strstr($mode, 'a')
            || strstr($mode, '+')
        );
    }

    public function write($string) : int
    {
        if (! $this->resource) {
            throw Exception\UnwritableStreamException::dueToMissingResource();
        }
        if (! $this->isWritable()) {
            throw Exception\UnwritableStreamException::dueToConfiguration();
        }
        $result = fwrite($this->resource, $string);
        if (false === $result) {
            throw Exception\UnwritableStreamException::dueToPhpError();
        }
        return $result;
    }

    public function isReadable() : bool
    {
        if (! $this->resource) {
            return false;
        }
        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];
        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    public function read($length) : string
    {
        if (! $this->resource) {
            throw Exception\UnreadableStreamException::dueToMissingResource();
        }
        if (! $this->isReadable()) {
            throw Exception\UnreadableStreamException::dueToConfiguration();
        }
        $result = fread($this->resource, $length);
        if (false === $result) {
            throw Exception\UnreadableStreamException::dueToPhpError();
        }
        return $result;
    }

    public function getContents() : string
    {
        if (! $this->isReadable()) {
            throw Exception\UnreadableStreamException::dueToConfiguration();
        }
        $result = stream_get_contents($this->resource);
        if (false === $result) {
            throw Exception\UnreadableStreamException::dueToPhpError();
        }
        return $result;
    }

    public function getMetadata($key = null)
    {
        if (null === $key) {
            return stream_get_meta_data($this->resource);
        }
        $metadata = stream_get_meta_data($this->resource);
        if (! array_key_exists($key, $metadata)) {
            return null;
        }
        return $metadata[$key];
    }

    private function setStream($stream, string $mode = 'r') : void
    {
        $error    = null;
        $resource = $stream;
        if (is_string($stream)) {
            set_error_handler(function ($e) use (&$error) {
                if ($e !== E_WARNING) {
                    return;
                }
                $error = $e;
            });
            $resource = fopen($stream, $mode);
            restore_error_handler();
        }
        if ($error) {
            throw new Exception\InvalidArgumentException('Invalid stream reference provided');
        }
        if (! is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new Exception\InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }
        if ($stream !== $resource) {
            $this->stream = $stream;
        }
        $this->resource = $resource;
    }

}
