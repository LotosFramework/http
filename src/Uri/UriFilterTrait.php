<?php

namespace Lotos\Http\Uri;

trait UriFilterTrait
{

    private function filterScheme(string $scheme) : string
    {
        return preg_replace('#:(//)?$#', '', strtolower($scheme));
    }

    private function filterPath(string $path) : string
    {
        $path = preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $path
        );
        return '/' . ltrim($path, '/');
    }

    private function urlEncodeChar(array $matches) : string
    {
        return rawurlencode($matches[0]);
    }

    private function filterQuery(string $query) : string
    {
        $query = ltrim($query, '?');
        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            $param = explode('=', $part);
            if (!$param[1]) {
                $parts[$index] = $this->filterParam($param[0]);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->filterParam($param[0]),
                $this->filterParam($param[1])
            );
        }
        return implode('&', $parts);
    }

    private function filterFragment(string $fragment) : string
    {
        return $this->filterParam('%23' . substr($fragment, 1));
    }

    private function filterParam(string $param) : string
    {
         return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $param
        );
    }
}
