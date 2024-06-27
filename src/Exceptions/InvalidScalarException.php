<?php

declare(strict_types=1);

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
        return self::makeTranslatable('Only php scalars are accepted, %s provided.', [ $scalar ]);
    }
}
