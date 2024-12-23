<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 * Class InvalidPropertyException
 * @package app\Models\ResourceDefinition\Exceptions
 */
class InvalidPropertyException extends ResourceException
{
    public static function create($name, $className): \CatLab\Charon\Exceptions\CharonException
    {
        return self::makeTranslatable('Invalid property %s in %s.', [ $name, $className ]);
    }
}
