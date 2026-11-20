<?php

declare(strict_types=1);

namespace CatLab\Charon\Resolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Models\Database\LimitParameter;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\Enums\FilterOperator;
use CatLab\Charon\Exceptions\InvalidFilterException;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Models\ConditionFilter;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;

/**
 * Class QueryAdapter
 * @package CatLab\Charon\Resolvers
 */
abstract class QueryAdapter extends ResolverBase implements \CatLab\Charon\Interfaces\QueryAdapter
{
    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @param $records
     * @param $skip
     */
    abstract public function applyLimit(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder,
        $records,
        $skip
    );

    /**
     * Apply a simple 'where' filter on the query builder, called in cases there are no
     * model specific filters (which is in most cases).
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param Field $field
     * @param $queryBuilder
     * @param $value
     * @param string $operator
     * @return mixed
     */
    abstract protected function applySimpleWhere(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $value,
        $operator = Operator::EQ
    );

    /**
     * Apply a simple 'sorting' on the query builder, called in cases there are no
     * model specific sorting methods (which is in most cases)
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param Field $field
     * @param $queryBuilder
     * @param string $direction
     * @return mixed
     */
    abstract protected function applySimpleSorting(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $direction = 'asc'
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @return
     */
    abstract public function countRecords(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @return
     */
    abstract public function getRecords(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder
    );

    /**
     * @param ResourceTransformer $transformer
     * @param $entityCollection
     * @param RelationshipField $field
     * @param Context $context
     * @return void
     */
    public function eagerLoadRelationship(
        ResourceTransformer $transformer,
        $queryBuilder,
        RelationshipField $field,
        Context $context
    ): void {
        $this->callEntitySpecificMethodIfExists(
            $transformer,
            $field,
            $context,
            self::EAGER_LOAD_METHOD_PREFIX,
            [
                $queryBuilder
            ]
        );
    }

    /**
     * Apply a filter to a query builder.
     * (Used for filtering or searching entries on filterable/searchble fields)
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param Field $field
     * @param $queryBuilder
     * @param $value
     * @param string $operator
     * @return void
     */
    public function applyPropertyFilter(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $value,
        $operator = Operator::EQ
    ): void {
        $method = $this->getEntitySpecificMethod($transformer, $field, $context, self::FILTER_METHOD_PREFIX);
        if ($method !== null) {
            [ $callable, $routeParameters ] = $method;

            // filter<Name>($query, ConditionFilter $filter, $context, $entityClass):
            // the method says, by the type of its parameter, that it handles every operator.
            if ($this->acceptsConditionFilter($callable, count($routeParameters) + 1)) {
                call_user_func_array($callable, array_merge($routeParameters, [
                    $queryBuilder,
                    new ConditionFilter($field, $operator, $value),
                    $context,
                    $definition->getEntityClassName()
                ]));
                return;
            }

            // filter<Name>($query, $value, $operator, ...) predates the conditions of
            // FilterOperator and may ignore $operator ("is not X" would return X).
            if (FilterOperator::isConditionOnly((string) $operator)) {
                throw InvalidFilterException::notAllowed($field->getDisplayName(), (string) $operator);
            }

            call_user_func_array($callable, array_merge($routeParameters, [
                $queryBuilder,
                $value,
                $operator,
                $context,
                $definition->getEntityClassName()
            ]));
            return;
        }

        $this->applySimpleWhere($transformer, $definition, $context, $field, $queryBuilder, $value, $operator);
    }

    /**
     * Whether the callable's parameter at $position is declared with a type
     * that a ConditionFilter satisfies (ConditionFilter itself, or a union
     * containing it). Untyped, mixed and object parameters do not count: they
     * are the old signature.
     * @param callable|string $callable
     * @param int $position
     * @return bool
     */
    protected function acceptsConditionFilter($callable, int $position): bool
    {
        try {
            $reflection = is_string($callable) && str_contains($callable, '::') ?
                new \ReflectionMethod($callable) :
                new \ReflectionFunction(\Closure::fromCallable($callable));
        } catch (\ReflectionException $e) {
            return false;
        }

        $parameters = $reflection->getParameters();
        if (!isset($parameters[$position])) {
            return false;
        }

        $type = $parameters[$position]->getType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [ $type ];

        foreach ($types as $namedType) {
            if (
                $namedType instanceof \ReflectionNamedType &&
                !$namedType->isBuiltin() &&
                is_a($namedType->getName(), ConditionFilter::class, true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param Field $field
     * @param $queryBuilder
     * @param string $direction
     */
    public function applyPropertySorting(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $direction = 'asc'
    ): void {
        // do we have a specific 'filter' method?
        if ($this->callEntitySpecificMethodIfExists(
            $transformer,
            $field,
            $context,
            self::SORT_METHOD_PREFIX,
            [
                $queryBuilder,
                $direction,
                $context,
                $definition->getEntityClassName()
            ]
        )
        ) {
            return;
        }

        $this->applySimpleSorting($transformer, $definition, $context, $field, $queryBuilder, $direction);
    }
}
