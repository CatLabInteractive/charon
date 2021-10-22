<?php

namespace CatLab\Charon\Exceptions;

/**
 * Class NoInputParsersSet
 * @package CatLab\Charon\Exceptions
 */
class NoInputParsersSet extends InputDataException
{
    public static function make()
    {
        return self::makeTranslatable('Failed parsing any input: no input parsers were set. Make sure to set input parsers in your context.');
    }
}
