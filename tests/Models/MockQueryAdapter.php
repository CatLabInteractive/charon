<?php

namespace Tests\Models;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\QueryAdapter;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;

class MockQueryAdapter implements QueryAdapter
{

    /**
     * @inheritDoc
     */
    public function getChildByIdentifiers(ResourceTransformer $transformer, RelationshipField $field, $parentEntity, PropertyValueCollection $identifiers, Context $context)
    {
        // TODO: Implement getChildByIdentifiers() method.
    }

    /**
     * @inheritDoc
     */
    public function getQualifiedName(Field $field)
    {
        // TODO: Implement getQualifiedName() method.
    }

    /**
     * @inheritDoc
     */
    public function eagerLoadRelationship(ResourceTransformer $transformer, $entityCollection, RelationshipField $field, Context $context)
    {
        // TODO: Implement eagerLoadRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function applyPropertyFilter(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $value, $operator = Operator::EQ)
    {
        // TODO: Implement applyPropertyFilter() method.
    }

    /**
     * @inheritDoc
     */
    public function applyPropertySorting(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $direction = 'asc')
    {
        // TODO: Implement applyPropertySorting() method.
    }

    /**
     * @inheritDoc
     */
    public function applyLimit(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder, $records, $skip)
    {
        // TODO: Implement applyLimit() method.
    }
}
