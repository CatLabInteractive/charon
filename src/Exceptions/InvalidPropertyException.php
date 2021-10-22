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
        return self::makeTranslatable('Invalid property %s in %s.', [ $name, $className ]);
    }
}
