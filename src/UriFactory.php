<?php

namespace Lotos\Http;

use Psr\Http\Message\{UriInterface, UriFactoryInterface};
use Lotos\Http\Uri\Uri;

class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = null): UriInterface
    {
        return new Uri($uri);
    }
}
