<?php

namespace Lotos\Http;

use Psr\Http\Message\{ServerRequestInterface, ServerRequestFactoryInterface};
use Lotos\Http\ServerRequest\{ServerRequest, ServerRequestFactoryTrait};
use Lotos\Http\Uri\Uri;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    use ServerRequestFactoryTrait;

    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []) : ServerRequestInterface {
        $uploadedFiles = [];
        return new ServerRequest(
            $serverParams,
            $uploadedFiles,
            $uri,
            $method,
            'php://temp'
        );
    }

    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) : ServerRequest {
        $server = self::getServer($server ?? $_SERVER);
        $files   = self::getFiles($files ?? $_FILES);
        $headers = self::getSapiHeaders($server);
        $cookies = self::getCookies($cookies, $headers);

        return new ServerRequest(
            $server,
            $files,
            self::getUriFromSapi($server, $headers),
            self::getMethod($server),
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $body ?: $_POST,
            self::getProtocolVersion($server)
        );
    }

}
