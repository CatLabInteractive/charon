<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class ValueUndefined
 * @package CatLab\Charon\Exceptions
 */
class ValueUndefined extends ResourceException
{
    /**
     * @param $valueName
     * @return ValueUndefined
     */
    public static function make($valueName)
    {
        return new self("Value " . $valueName . " is not defined.");
    }
}