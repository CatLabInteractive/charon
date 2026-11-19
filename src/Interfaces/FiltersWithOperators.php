<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

/**
 * Marks an entity whose filter<Name>($query, $value, $operator, ...) methods
 * honour every operator, the conditions of FilterOperator (NEQ, EMPTY,
 * NOT_EMPTY) included. Without it, such a method only gets = and SEARCH, and
 * a request with another condition on that field is refused
 * (InvalidFilterException).
 */
interface FiltersWithOperators
{
}
