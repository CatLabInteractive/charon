<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class NoInputParsersSet
 * @package CatLab\Charon\Exceptions
 */
class NoInputParsersSet extends ResourceException
{
    public static function make()
    {
        return new self('Failed parsing any input: no input parsers were set. Make sure to set input parsers in your context.');
    }
}