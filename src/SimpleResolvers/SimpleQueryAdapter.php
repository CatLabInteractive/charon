<?php

namespace CatLab\Charon\SimpleResolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Resolvers\QueryAdapter;

/**
 * Class SimpleQueryAdapter
 * @package CatLab\Charon\SimpleResolvers
 */
class SimpleQueryAdapter extends QueryAdapter
{

    public function getQualifiedName(Field $field)
    {
        // TODO: Implement getQualifiedName() method.
    }

    public function getRecords(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder)
    {
        // TODO: Implement getRecords() method.
    }

    public function getChildByIdentifiers(ResourceTransformer $transformer, RelationshipField $field, $parentEntity, Identifier $identifier, Context $context)
    {
        // TODO: Implement getChildByIdentifiers() method.
    }

    public function applyLimit(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder, $records, $skip)
    {
        // TODO: Implement applyLimit() method.
    }

    protected function applySimpleWhere(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $value, $operator = Operator::EQ)
    {
        // TODO: Implement applySimpleWhere() method.
    }

    protected function applySimpleSorting(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $direction = 'asc')
    {
        // TODO: Implement applySimpleSorting() method.
    }

    public function countRecords(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder)
    {
        // TODO: Implement countRecords() method.
    }
}
