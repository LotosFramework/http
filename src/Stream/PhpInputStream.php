<?php

namespace Lotos\Http\Stream;

use Psr\Http\Message\StreamInterface;

class PhpInputStream extends Stream implements StreamInterface
{
    private $reachedEof = false;
    private $cache = '';

    public function __construct($stream = 'php://input')
    {
        parent::__construct($stream, 'r');
    }

    public function __toString() : string
    {
        if ($this->reachedEof) {
            return $this->cache;
        }
        $this->getContents();
        return $this->cache;
    }

    public function isWritable() : bool
    {
        return false;
    }

    public function read($length) : string
    {
        $content = parent::read($length);
        if (! $this->reachedEof) {
            $this->cache .= $content;
        }
        if ($this->eof()) {
            $this->reachedEof = true;
        }
        return $content;
    }

    public function getContents($maxLength = -1) : string
    {
        if ($this->reachedEof) {
            return $this->cache;
        }
        $contents     = stream_get_contents($this->resource, $maxLength);
        $this->cache .= $contents;
        if ($maxLength === -1 || $this->eof()) {
            $this->reachedEof = true;
        }
        return $contents;
    }
}
