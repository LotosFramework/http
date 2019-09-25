<?php

namespace Lotos\Http\Message;

trait MessageValidatorTrait
{
    private function ensureNotEmpty($param) : void
    {
        if(empty($param)) {
            throw new EmptyPropertyException;
        }
    }
}
