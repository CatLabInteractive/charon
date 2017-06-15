<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Interfaces\PropertyResolver as PropertyResolverContract;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Interface PropertySetter
 * @package CatLab\RESTResource\Contracts
 */
interface PropertySetter
{
    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param Field $field
     * @param mixed $value
     * @param Context $context
     */
    public function setEntityValue(
        ResourceTransformer $transformer,
        $entity,
        Field $field,
        $value,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param mixed $value
     * @param Context $context
     */
    public function setChild(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        $value,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param Context $context
     */
    public function clearChild(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        Context $context
    );

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RelationshipField $field
     * @param $childEntities
     * @param Context $context
     */
    public function addChildren(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    );

    /**
     * Edit a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RelationshipField $field
     * @param $childEntities
     * @param Context $context
     */
    public function editChildren(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolverContract $propertyResolver
     * @param $entity
     * @param RelationshipField $field
     * @param PropertyValueCollection[] $identifiers
     * @param Context $context
     * @return mixed
     */
    public function removeAllChildrenExcept(
        ResourceTransformer $transformer,
        PropertyResolverContract $propertyResolver,
        $entity,
        RelationshipField $field,
        array $identifiers,
        Context $context
    );

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RelationshipField $field
     * @param $childEntities
     * @param Context $context
     */
    public function removeChildren(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        $childEntities,
        Context $context
    );
}