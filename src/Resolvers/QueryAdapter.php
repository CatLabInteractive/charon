<?php

declare(strict_types=1);

namespace CatLab\Charon\Resolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Models\Database\LimitParameter;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\Exceptions\NotImplementedException;
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
        // do we have a specific 'filter' method?
        if ($this->callEntitySpecificMethodIfExists(
            $transformer,
            $field,
            $context,
            self::FILTER_METHOD_PREFIX,
            [
                $queryBuilder,
                $value,
                $operator,
                $context,
                $definition->getEntityClassName()
            ]
        )
        ) {
            return;
        }

        $this->applySimpleWhere($transformer, $definition, $context, $field, $queryBuilder, $value, $operator);
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
