<?php

declare(strict_types=1);

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
    public static function make($valueName): \CatLab\Charon\Exceptions\CharonException
    {
        return self::makeTranslatable('Value %s is not defined.', [ $valueName ]);
    }
}
