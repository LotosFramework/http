<?php

namespace Lotos\Http;

use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};
use Lotos\Http\Stream\Stream;

class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = '') : StreamInterface
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $content);
        rewind($resource);
        return $this->createStreamFromResource($resource);
    }

    public function createStreamFromFile(
        string $file,
        string $mode = 'r') : StreamInterface {
        return new Stream($file, $mode);
    }

    public function createStreamFromResource($resource) : StreamInterface
    {
        try {
            $this->ensureValidResource($resource);
            return new Stream($resource);
        } catch(InvalidResourceException $e) {
            throw new InvalidResourceException($e->getMessage());
        }
    }

    private function ensureValidResource($resource) : void
    {
        if(!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new InvalidResourceException;
        }
    }
}
