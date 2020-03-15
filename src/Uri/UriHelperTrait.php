<?php

namespace Lotos\Http\Uri;

use Psr\Http\Message\UriInterface;
use Lotos\Http\Uri\Exception\InvalidTypeException;

trait UriHelperTrait
{
    private function parseUri(string $uri) : void
    {
        extract(parse_url($uri));
        $this->parseScheme($scheme);
        $this->parseUserInfo($user);
        $this->parseHost($host);
        $this->parsePort($port);
        $this->parsePath($path);
        $this->parseQuery($query);
        $this->parseFragment($fragment);
        if (isset($pass)) {
            $this->userInfo .= ':' . $pass;
        }
    }

    private function parseScheme(string $scheme = null) : void
    {
        $this->scheme = !is_null($scheme)
            ? $this->filterScheme($scheme)
            : '';
    }

    private function parseUserInfo(string $user = null) : void
    {
        $this->userInfo = !is_null($user)
            ? $this->filterUserInfoPart($user)
            : '';
    }

    private function parseHost(string $host = null) : void
    {
        $this->host = !is_null($host)
            ? strtolower($host)
            : '';
    }

    private function parsePort(int $port = null) : void
    {
        $this->port = !is_null($port)
            ? $port
            : null;
    }

    private function parsePath(string $path = null) : void
    {
        $this->path = !is_null($path)
            ? $this->filterPath($path)
            : '';
    }

    private function parseQuery(string $query = null) : void
    {
        $this->query = !is_null($query)
            ? $this->filterQuery($query)
            : '';
    }

    private function parseFragment(string $fragment = null) : void
    {
        $this->fragment = !is_null($fragment)
            ? $this->filterFragment($fragment)
            : '';
    }

    private function getUriInstance($uri = null) : UriInterface
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

        throw new InvalidTypeException;
    }

    public function addVars(array $vars) : void
    {
        $this->vars = $vars;
    }

    public function getVars() : array
    {
        return $this->vars;
    }
}
