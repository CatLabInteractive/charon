<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 * A filter in the request that cannot be applied: an unknown condition, or
 * one the field does not allow. Answer it with a 400.
 */
class InvalidFilterException extends InputDataException
{
    public static function unknownCondition(string $field, string $condition): static
    {
        return self::makeTranslatable(
            'Unknown filter condition "%s" on "%s". Use one of: %s.',
            [ $condition, $field, implode(', ', \CatLab\Charon\Enums\FilterOperator::getRequestNames()) ]
        );
    }

    public static function notAllowed(string $field, string $condition): static
    {
        return self::makeTranslatable(
            'Filter condition "%s" is not available on "%s".',
            [ $condition, $field ]
        );
    }
}
