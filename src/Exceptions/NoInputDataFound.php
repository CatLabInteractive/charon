<?php


namespace CatLab\Charon\Exceptions;

/**
 * Class NoInputDataFound
 * @package CatLab\Charon\Exceptions
 */
class NoInputDataFound extends ResourceException
{
    public static function make()
    {
        return new self('Failed parsing any input: no input data found. Did you provide a proper content type?');
    }
}
