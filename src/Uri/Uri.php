<?php

namespace Lotos\Http\Uri;

use Psr\Http\Message\UriInterface;
use Lotos\Http\Uri\{UriHelperTrait, UriValidatorTrait, UriFilterTrait};
use Lotos\Http\Uri\Exception\{
    NotEqualElementsException,
    InvalidTypeException,
    InvalidFirstSymbolException,
    InvalidIntervalException,
    InvalidPortValueException,
    SchemeMustBeAStringException,
    UserMustBeAStringException,
    FragmentMustBeAStringException,
    FragmentMustStartWithSharpSymbolException,
    EmptyPropertyException,
    HostMustBeAStringException,
    NonStandardPortException,
    PortMustBeAIntegerException,
    PathMustBeAStringException,
    PathMustBeWithoutQueryException,
    PathMustBeWithoutFragmentException,
    QueryMustBeAStringException,
    QueryMustBeWithoutFragmentException,
    QueryMustStartWithQuestionMarkException
};

class Uri implements UriInterface
{

    use UriHelperTrait;
    use UriValidatorTrait;
    use UriFilterTrait;

    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    private $scheme;
    private $host;
    private $userInfo;
    private $port;
    private $path;
    private $query;
    private $fragment;

    public function __construct($uri = null)
    {
        if(!is_null($uri)) {
            $this->parseUri($uri);
        }
    }

    public function getScheme() : string
    {
        return $this->scheme;
    }

    public function getAuthority() : string
    {
        try {
            $this->ensureNotEmptyProperty($this->host);
            $authority = $this->host;

            if($this->userInfo) {
                $authority = $this->userInfo . '@' . $authority;
            }

            $this->ensureStandardPort();

            return $authority;
        } catch(EmptyPropertyException $e) {
            return '';
        } catch(NonStandardPortException $e) {
            $authority .= ':' . $this->port;
            return $authority;
        }
    }

    public function getUserInfo() : string
    {
        return $this->userInfo;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getPort() : ?int
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port)
            ? $this->port
            : null;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getQuery() : string
    {
        return $this->query;
    }

    public function getFragment() : string
    {
        return $this->fragment;
    }

    //Тут можно было просто указать тип аргумента $scheme
    // и не делать хренову тучу проверок,
    // но Psr is Psr. fck!
    public function withScheme($scheme) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($scheme);
            $this->ensureValidStringType($scheme);
            $scheme = $this->filterScheme($scheme);
            $this->ensureNotEmptyProperty($scheme);
            $this->ensureEquals($scheme, $this->scheme);
            return $this;
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->scheme = $scheme;
            return $clone;
        } catch(EmptyPropertyException | InvalidTypeException $e) {
            throw new SchemeMustBeAStringException($e->getMessage());
        }
    }
    //Тут та же хрень
    public function withUserInfo($user, $password = null) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($user);
            $this->ensureValidStringType($user);
            if($password) {
                $this->ensureValidStringType();
            }
            return $this;
        } catch(EmptyPropertyException | InvalidTypeException $e) {
            throw new UserMustBeAStringException($e->getMessage());
        }
    }
    //И тут
    public function withHost($host) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($host);
            $this->ensureValidStringType($host);
            $this->ensureEquals($host, $this->host);
            return $this;
        } catch(EmptyPropertyException | InvalidTypeException $e) {
            throw new HostMustBeAStringException($e->getMessage());
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->host = $host;
            return $clone;
        }
    }
    //и здесь
    public function withPort($port = null) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($host);
            $this->ensureValidIntType($port);
            $this->ensureValidInterval($port, 1, 65535);
            $this->ensureEquals($port, $this->port);
        } catch(EmptyPropertyException | InvalidTypeException $e) {
            throw new PortMustBeAIntegerException($e->getMessage());
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->port = $port;
            return $clone;
        } catch(InvalidIntervalException $e) {
            throw new InvalidPortValueException($e->getMessage());
        }
    }
    //и тут блин тоже!
    public function withPath($path) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($path);
            $this->ensureValidStringType($path);
            $this->ensurePathWithoutQuery($path);
            $this->ensurePathWithoutFragment($path);
            $path = $this->filterPath($path);
            $this->ensureEquals($path, $this->path);
            return $this;
        } catch(EmptyPropertyException | InvalidTypeException $e) {
            throw new PathMustBeAStringException($e->getMessage());
        } catch(PathMustBeWithoutQueryException $e) {
            throw new PathMustBeWithoutQueryException($e->getMessage());
        } catch(PathMustBeWithoutFragmentException $e) {
            throw new PathMustBeWithoutFragmentException($e->getMessage());
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->path = $path;
            return $clone;
        }
    }
    //ну в общем понятно, да?
    public function withQuery($query) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($query);
            $this->ensureValidStringType($query);
            $this->ensureQueryWithoutFragment($query);
            $query = $this->filterQuery($query);
            $this->ensureEquals($query, $this->query);
            return $this;
        } catch(EmptyPropertyException $e) {
            return $this;
        } catch(InvalidTypeException $e) {
            throw new QueryMustBeAStringException($e->getMessage());
        } catch(QueryMustBeWithoutFragmentException $e) {
            throw new QueryMustBeWithoutFragmentException($e->getMessage());
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->query = $query;
            return $clone;
        }
    }
    //ну и на последок сюрприз-сюрприз
    public function withFragment($fragment) : UriInterface
    {
        try {
            $this->ensureNotEmptyProperty($fragment);
            $this->ensureValidStringType($fragment);
            $this->ensureValidFirstSymbol('#', $fragment);
            $this->filterFragment($fragment);
            $this->ensureEquals($fragment, $this->fragment);
        } catch(EmptyPropertyException $e) {
            return $this;
        } catch(InvalidTypeException $e) {
            throw new FragmentMustBeAStringException($e->getMessage());
        } catch(NotEqualElementsException $e) {
            $clone = clone $this;
            $clone->fragment = $fragment;
            return $clone;
        } catch(InvalidFirstSymbolException $e) {
            throw new FragmentMustStartWithSharpSymbolException($e->getMessage());
        }
    }

    private function getUriString(
        string $scheme,
        string $authority,
        string $path,
        string $query,
        string $fragment) : string {

        $uri = '';

        if ('' !== $scheme) {
            $uri .= sprintf('%s:', $scheme);
        }

        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }

        if ('' !== $path && '/' !== substr($path, 0, 1)) {
            $path = '/' . $path;
        }

        $uri .= $path;

        if ('' !== $query) {
            $uri .= sprintf('?%s', $query);
        }

        if ('' !== $fragment) {
            $uri .= sprintf('#%s', $fragment);
        }

        return $uri;
    }


    public function __toString() : string
    {
        return $this->uriString ?? $this->getUriString(
            $this->scheme ?? '',
            $this->getAuthority() ?? '',
            $this->getPath() ?? '',
            $this->query ?? '',
            $this->fragment ?? ''
        );
    }

    public function __clone()
    {
        $this->uriString = null;
    }

}
