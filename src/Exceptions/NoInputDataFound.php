<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 * Class NoInputDataFound
 * @package CatLab\Charon\Exceptions
 */
class NoInputDataFound extends InputDataException
{
    /**
     * @return NoInputDataFound
     */
    public static function make(): \CatLab\Charon\Exceptions\CharonException
    {
        return self::makeTranslatable('Failed parsing any input: no input data found. Did you provide a proper content type?');
    }
}
