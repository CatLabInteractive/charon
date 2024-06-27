<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 *
 */
class InputDecodeException extends InputDataException
{
    /**
     * @return InputDecodeException
     */
    public static function make($rawContent = null)
    {
        return self::makeTranslatable('Could not decode body.');
    }
}
