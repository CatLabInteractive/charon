<?php

namespace CatLab\Charon\Exceptions;

/**
 *
 */
class InputDecodeException extends ResourceException
{
    /**
     * @return InputDecodeException
     */
    public static function make($rawContent = null)
    {
        return new self('Could not decode body');
    }
}
