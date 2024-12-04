<?php

declare(strict_types=1);

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

    public function getQualifiedName(Field $field): void
    {
        // TODO: Implement getQualifiedName() method.
    }

    public function getRecords(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder): void
    {
        // TODO: Implement getRecords() method.
    }

    public function getChildByIdentifiers(ResourceTransformer $transformer, RelationshipField $field, $parentEntity, Identifier $identifier, Context $context): void
    {
        // TODO: Implement getChildByIdentifiers() method.
    }

    public function applyLimit(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder, $records, $skip): void
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

    public function countRecords(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder): void
    {
        // TODO: Implement countRecords() method.
    }
}
