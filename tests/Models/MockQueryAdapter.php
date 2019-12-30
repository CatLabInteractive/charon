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

class MockQueryAdapter extends \CatLab\Charon\Resolvers\QueryAdapter
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
    protected function applySimpleWhere(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $value, $operator = Operator::EQ)
    {
        // TODO: Implement applySimpleWhere() method.
    }

    /**
     * @inheritDoc
     */
    protected function applySimpleSorting(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, Field $field, $queryBuilder, $direction = 'asc')
    {
        // TODO: Implement applySimpleSorting() method.
    }
}
