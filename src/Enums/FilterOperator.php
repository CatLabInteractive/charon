<?php

declare(strict_types=1);

namespace CatLab\Charon\Enums;

use CatLab\Base\Enum\Operator;

/**
 * The operators a filterable field accepts in a request.
 *
 * A plain parameter (?name=Foo) still filters on equality. An array picks the
 * operator: ?name[op]=contains&name[value]=Foo, ?url[op]=not_empty.
 *
 * Equality, inequality and 'contains' use the operators of catlab/base, so
 * query adapters that already handle those need nothing new. EMPTY and
 * NOT_EMPTY are new: no value, the field is (not) null or an empty string.
 */
class FilterOperator
{
    public const EMPTY = 'EMPTY';

    public const NOT_EMPTY = 'NOT_EMPTY';

    public const REQUEST_EQ = 'eq';
    public const REQUEST_NEQ = 'neq';
    public const REQUEST_CONTAINS = 'contains';
    public const REQUEST_EMPTY = 'empty';
    public const REQUEST_NOT_EMPTY = 'not_empty';

    /**
     * Request name => operator.
     */
    private const OPERATORS = [
        self::REQUEST_EQ => Operator::EQ,
        self::REQUEST_NEQ => Operator::NEQ,
        self::REQUEST_CONTAINS => Operator::SEARCH,
        self::REQUEST_EMPTY => self::EMPTY,
        self::REQUEST_NOT_EMPTY => self::NOT_EMPTY,
    ];

    /**
     * The operator for a request name ('' or null is equality), null when unknown.
     */
    public static function fromRequest(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return Operator::EQ;
        }

        return self::OPERATORS[$name] ?? null;
    }

    /**
     * Whether the operator compares with a value (EMPTY and NOT_EMPTY do not).
     */
    public static function takesValue(string $operator): bool
    {
        return $operator !== self::EMPTY && $operator !== self::NOT_EMPTY;
    }

    /**
     * @return string[] the request names, in the order a filter form offers them.
     */
    public static function getRequestNames(): array
    {
        return array_keys(self::OPERATORS);
    }
}
