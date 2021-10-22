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
        return self::makeTranslatable('Value %s is not defined.', [ $valueName ]);
    }
}
