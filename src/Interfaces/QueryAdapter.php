<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;

/**
 * Interface QueryAdapter
 * @package CatLab\Charon\Interfaces
 */
interface QueryAdapter
{
    /**
     * @param ResourceTransformer $transformer
     * @param RelationshipField $field
     * @param mixed $parentEntity
     * @param Identifier $identifier,
     * @param Context $context
     * @return mixed
     */
    public function getChildByIdentifiers(
        ResourceTransformer $transformer,
        RelationshipField $field,
        $parentEntity,
        Identifier $identifier,
        Context $context
    );

    /**
     * @param Field $field
     * @return mixed
     */
    public function getQualifiedName(Field $field);

    /**
     * @param ResourceTransformer $transformer
     * @param $entityCollection
     * @param RelationshipField $field
     * @param Context $context
     * @return void
     */
    public function eagerLoadRelationship(
        ResourceTransformer $transformer,
        $entityCollection,
        RelationshipField $field,
        Context $context
    );

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
    );

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
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @param $records
     * @param $skip
     * @return void
     */
    public function applyLimit(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder,
        $records,
        $skip
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @return
     */
    public function countRecords(
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
    public function getRecords(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder
    );
}
