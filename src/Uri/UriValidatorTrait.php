<?php

namespace Lotos\Http\Uri;

use Lotos\Http\Uri\Exception\{
    EmptyPropertyException,
    NonStandardPortException,
    InvalidTypeException,
    NotEqualElementsException,
    InvalidIntervalException,
    PathMustBeWithoutQueryException,
    PathMustBeWithoutFragmentException,
    InvalidFirstSymbolException,
    QueryMustBeWithoutFragmentException
};

trait UriValidatorTrait
{
    private $allowedPorts = [
        'http' => 80,
        'https' => 443
    ];

    private function ensureNotEmptyProperty(string $property) : void
    {
        if(empty($property)) {
            throw new EmptyPropertyException('Property is empty');
        }
    }

    private function ensureStandardPort() : void
    {
        if($this->isNonStandardPort()) {
            throw new NonStandardPortException('Port ' . $this->port . '
                is not standart port for ' . $this->scheme);
        }
    }

    private function ensureValidStringType($value) : void
    {
        if(!is_string($value)) {
            throw new InvalidTypeException('Parameter must be a string');
        }
    }

    private function ensureEquals($value1, $value2) : void
    {
        if($value1 !== $value2) {
            throw new NotEqualElementsException;
        }
    }

    private function ensureValidIntType($value) : void
    {
        if(is_int($value) === false) {
            throw new InvalidTypeException('Parameter ' . $$value . '
                must be a integer');
        }
    }

    private function ensureValidInterval($value, $min, $max) : void
    {
        if($value<$min || $value>$max) {
            throw new InvalidIntervalException('Parameter ' . $$value . '
                must be between ' . $min . ' and ' . $max);
        }
    }

    private function ensurePathWithoutQuery($path) : void
    {
        if(strpos($path, '?') !== false) {
            throw new PathMustBeWithoutQueryException;
        }
    }

    private function ensurePathWithoutFragment($path) : void
    {
        if(strpos($path, '#') !== false) {
            throw new PathMustBeWithoutFragmentException;
        }
    }

    private function ensureValidFirstSymbol($symbol, $query) : void
    {
        if(substr($query, 0, 1) !== $symbol) {
            throw new InvalidFirstSymbolException;
        }
    }

    private function ensureQueryWithoutFragment($path) : void
    {
        if(strpos($path, '#') !== false) {
            throw new QueryMustBeWithoutFragmentException;
        }
    }

    private function isNonStandardPort() : bool
    {
        if ($this->scheme === '') {
            return $this->host === '' || $this->port !== null;
        }
        if ($this->host === '' || $this->port === null) {
            return false;
        }
        return (!isset($this->allowed[$this->scheme]) ||
                $this->port !== $this->allowed[$this->scheme]);
    }
}
