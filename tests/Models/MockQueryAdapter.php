<?php

namespace Tests\Models;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Models\Database\LimitParameter;
use CatLab\Base\Models\Database\OrderParameter;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Base\Models\Database\WhereParameter;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use Countable;

if (!function_exists('is_countable')) {
    function is_countable($var) {
        return (is_array($var) || $var instanceof Countable);
    }
}

/**
 * Class MockQueryAdapter
 * @package Tests\Models
 */
class MockQueryAdapter extends \CatLab\Charon\Resolvers\QueryAdapter
{
    /**
     * @inheritDoc
     */
    public function getChildByIdentifiers(ResourceTransformer $transformer, RelationshipField $field, $parentEntity, Identifier $identifier, Context $context)
    {
        // TODO: Implement getChildByIdentifiers() method.
    }

    /**
     * @inheritDoc
     */
    public function getQualifiedName(Field $field)
    {
        return $field->getResourceDefinition()->getEntityClassName() . '.' . $field->getName();
    }

    /**
     * @inheritDoc
     */
    protected function applySimpleWhere(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $value,
        $operator = Operator::EQ
    ) {
        /** @var SelectQueryParameters $queryBuilder */
        $queryBuilder->where(new WhereParameter($this->getQualifiedName($field), '=', $value));
    }

    /**
     * @inheritDoc
     */
    protected function applySimpleSorting(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        $queryBuilder,
        $direction = 'asc'
    ) {
        /** @var SelectQueryParameters $queryBuilder */
        $queryBuilder->orderBy(new OrderParameter($this->getQualifiedName($field), $direction));
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param $queryBuilder
     * @param $records
     * @param $skip
     */
    public function applyLimit(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $queryBuilder,
        $records,
        $skip
    ) {
        /** @var SelectQueryParameters $queryBuilder */
        if ($skip) {
            $queryBuilder->limit(new LimitParameter($records));
        } else {
            $queryBuilder->limit(new LimitParameter($skip, $records));
        }
    }

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
    ) {
        if (is_countable($queryBuilder)) {
            return count($queryBuilder);
        } else {
            //throw new \InvalidArgumentException('countRecords doesn\'t know how to handle ' . get_class($queryBuilder));
            return 10;
        }
    }

    /**
     * @inheritDoc
     */
    public function getRecords(ResourceTransformer $transformer, ResourceDefinition $definition, Context $context, $queryBuilder)
    {
        return $queryBuilder;
    }
}
