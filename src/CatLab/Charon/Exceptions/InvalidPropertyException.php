<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidPropertyException
 * @package app\Models\ResourceDefinition\Exceptions
 */
class InvalidPropertyException extends ResourceException
{
    public static function create($name, $className)
    {
        return new self("Invalid property '" . $name . "' in " . $className);
    }
}