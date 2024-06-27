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
    public static function make($rawContent = null): \CatLab\Charon\Exceptions\CharonException
    {
        return self::makeTranslatable('Could not decode body.');
    }
}
