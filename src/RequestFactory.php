<?php

namespace Lotos\Http;

use Psr\Http\Message\{RequestInterface, RequestFactoryInterface};
use Lotos\Http\Request\Request;

class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri) : RequestInterface
    {
        return new Request($uri, $method);
    }
}
