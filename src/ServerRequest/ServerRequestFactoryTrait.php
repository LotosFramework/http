<?php

namespace Lotos\Http\ServerRequest;

use Lotos\Http\{UriFactory, Uri\Uri};

trait ServerRequestFactoryTrait
{
    private static function getCookies($cookies, $headers) : array
    {
        return $cookies ?? (array_key_exists('cookie', $headers))
            ? explode('; ', $headers['cookie'])
            : [];
    }

    private static function getMethod(array $server) : string
    {
        return $server['REQUEST_METHOD'] ?? 'GET';
    }

    private static function getServer(array $server) : array
    {
        if(!empty($server['HTTP_AUTHORIZATION'])) {
            return $server;
        }
        $headers = apache_request_headers();
        if(!empty($headers['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $headers['authorization'];
        }
        return $server;
    }

    private static function getProtocolVersion()
    {
        if (! isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }
        if (! preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $server['SERVER_PROTOCOL'], $matches)) {
            throw Exception\UnrecognizedProtocolVersionException::forVersion(
                (string) $server['SERVER_PROTOCOL']
            );
        }
        return $matches['version'];
    }

    private static function getFiles(array $files) : array
    {
        return $files;
    }

    private static function getSapiHeaders(array $server) : array
    {
        $headers = [];
        foreach($server as $key => $value) {
            if(strpos($key, 'REDIRECT_') === 0) {
                $key = ltrim('REDIRECT_', $key);
                if(array_key_exists($key, $server)) {
                    continue;
                }
            }

            if($value === '') {
                continue;
            }

            if (strpos($key, 'HTTP_') === 0) {
                $name = strtr(strtolower(substr($key, 5)), '_', '-');
                $headers[$name] = $value;
                continue;
            }
            if (strpos($key, 'CONTENT_') === 0) {
                $name = 'content-' . strtolower(substr($key, 8));
                $headers[$name] = $value;
                continue;
            }
        }
        return $headers;
    }

    private static function parseCookieHeader($cookieHeader) : array
    {
        preg_match_all('(
            (?:^\\n?[ \t]*|;[ ])
            (?P<name>[!#$%&\'*+-.0-9A-Z^_`a-z|~]+)
            =
            (?P<DQUOTE>"?)
                (?P<value>[\x21\x23-\x2b\x2d-\x3a\x3c-\x5b\x5d-\x7e]*)
            (?P=DQUOTE)
            (?=\\n?[ \t]*$|;[ ])
        )x', $cookieHeader, $matches, PREG_SET_ORDER);
        $cookies = [];
        foreach ($matches as $match) {
            $cookies[$match['name']] = urldecode($match['value']);
        }
        return $cookies;
    }


    private static function getUriFromSapi(array $server, array $headers) : Uri
    {

        $getHeaderFromArray = function (string $name, array $headers, $default = null) {
            $header  = strtolower($name);
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (array_key_exists($header, $headers)) {
                $value = is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];
                return $value;
            }
            return $default;
        };

        $marshalHostAndPort = function (array $headers, array $server) use ($getHeaderFromArray) : array {
            $marshalHostAndPortFromHeader = function ($host) {
                if (is_array($host)) {
                    $host = implode(', ', $host);
                }
                $port = null;
                if (preg_match('|\:(\d+)$|', $host, $matches)) {
                    $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                    $port = (int) $matches[1];
                }
                return [$host, $port];
            };

            $marshalIpv6HostAndPort = function (array $server, string $host, ?int $port) : array {
                $host = '[' . $server['SERVER_ADDR'] . ']';
                $port = $port ?: 80;
                if ($port . ']' === substr($host, strrpos($host, ':') + 1)) {
                    $port = null;
                }
                return [$host, $port];
            };
            static $defaults = ['', null];
            if ($getHeaderFromArray('host', $headers, false)) {
                return $marshalHostAndPortFromHeader($getHeaderFromArray('host', $headers));
            }
            if (! isset($server['SERVER_NAME'])) {
                return $defaults;
            }
            $host = $server['SERVER_NAME'];
            $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;
            if (! isset($server['SERVER_ADDR'])
                || ! preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
            ) {
                return [$host, $port];
            }
            return $marshalIpv6HostAndPort($server, $host, $port);
        };
        $marshalRequestPath = function (array $server) : string {
            $iisUrlRewritten = array_key_exists('IIS_WasUrlRewritten', $server) ? $server['IIS_WasUrlRewritten'] : null;
            $unencodedUrl    = array_key_exists('UNENCODED_URL', $server) ? $server['UNENCODED_URL'] : '';
            if ('1' === $iisUrlRewritten && ! empty($unencodedUrl)) {
                return $unencodedUrl;
            }
            $requestUri = array_key_exists('REQUEST_URI', $server) ? $server['REQUEST_URI'] : null;
            if ($requestUri !== null) {
                return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }
            $origPathInfo = array_key_exists('ORIG_PATH_INFO', $server) ? $server['ORIG_PATH_INFO'] : null;
            if (empty($origPathInfo)) {
                return '/';
            }
            return $origPathInfo;
        };
        $uri = (new UriFactory)->createUri();
        $scheme = 'http';
        $marshalHttpsValue = function ($https) : bool {
            if (is_bool($https)) {
                return $https;
            }
            if (! is_string($https)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'SAPI HTTPS value MUST be a string or boolean; received %s',
                    gettype($https)
                ));
            }
            return 'off' !== strtolower($https);
        };
        if (array_key_exists('HTTPS', $server)) {
            $https = $marshalHttpsValue($server['HTTPS']);
        } elseif (array_key_exists('https', $server)) {
            $https = $marshalHttpsValue($server['https']);
        } else {
            $https = false;
        }
        if ($https
            || strtolower($getHeaderFromArray('x-forwarded-proto', $headers, '')) === 'https'
        ) {
            $scheme = 'https';
        }
        $uri = $uri->withScheme($scheme);

        [$host, $port] = $marshalHostAndPort($headers, $server);
        if (! empty($host)) {
            $uri = $uri->withHost($host);
            if (! empty($port)) {
                $uri = $uri->withPort($port);
            }
        }
        $path = $marshalRequestPath($server);
        $path = explode('?', $path, 2)[0];
        $query = '';
        if (isset($server['QUERY_STRING'])) {
            $query = $server['QUERY_STRING'];
        }
        $fragment = '';
        if (strpos($path, '#') !== false) {
            [$path, $fragment] = explode('#', $path, 2);
        }
        return $uri
            ->withPath($path)
            ->withFragment($fragment)
            ->withQuery($query);
    }
}
