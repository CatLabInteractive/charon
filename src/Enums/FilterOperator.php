<?php

declare(strict_types=1);

namespace CatLab\Charon\Enums;

use CatLab\Base\Enum\Operator;

/**
 * The conditions a filterable field accepts in a request.
 *
 * A plain parameter (?name=Foo) still filters on equality. A condition is
 * the key of an array, one parameter per condition:
 *
 *   ?name[neq]=Foo        name is not Foo (rows without a name included)
 *   ?name[contains]=oo    only on searchable() fields
 *   ?url[empty]=1         null or ''
 *   ?url[not_empty]=1
 *
 * Equality, inequality and 'contains' use the operators of catlab/base, so
 * query adapters that already handle those need nothing new. EMPTY and
 * NOT_EMPTY are new and take no value.
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
     * The operator for a request name, null when unknown.
     */
    public static function fromRequest(string|int|null $name): ?string
    {
        if (!is_string($name)) {
            return null;
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
     * Operators that only exist since conditions could be requested: entity
     * specific filter methods (filter<Name>()) that predate them may ignore
     * the operator, so they only get these when the entity opts in
     * (FiltersWithOperators).
     */
    public static function isConditionOnly(string $operator): bool
    {
        return in_array($operator, [ Operator::NEQ, self::EMPTY, self::NOT_EMPTY ], true);
    }

    /**
     * @return string[] the request names, in the order a filter form offers them.
     */
    public static function getRequestNames(): array
    {
        return array_keys(self::OPERATORS);
    }
}
