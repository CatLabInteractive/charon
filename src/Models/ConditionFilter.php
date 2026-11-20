<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

use CatLab\Charon\Enums\FilterOperator;

/**
 * The filter an entity's own filter<Name>() method receives when it asks for
 * one by type:
 *
 *   public static function filterTypeString($query, ConditionFilter $filter, Context $context, string $entityClass)
 *   {
 *       match ($filter->getOperator()) { ... };
 *   }
 *
 * Declaring the type is the method's promise that it handles every operator
 * (Operator::EQ, NEQ, SEARCH, GT/LT for cursors, FilterOperator::EMPTY and
 * NOT_EMPTY). A method that does not (the old ($query, $value, $operator, ...)
 * signature) only gets = and SEARCH; other conditions on its field are refused.
 */
class ConditionFilter extends Filter
{
    /**
     * Whether the operator compares with getValue() (EMPTY and NOT_EMPTY do not).
     */
    public function takesValue(): bool
    {
        return FilterOperator::takesValue((string) $this->getOperator());
    }
}
