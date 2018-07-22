<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidScalarException
 * @package CatLab\Charon\Exceptions
 */
class InvalidScalarException extends ResourceException
{
    /**
     * @param $scalar
     * @return InvalidScalarException
     */
    public static function make($scalar)
    {
        return new self("Only php scalars are accepted, " . $scalar . " provided.");
    }
}