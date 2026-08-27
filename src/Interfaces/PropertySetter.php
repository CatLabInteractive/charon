<?php

declare(strict_types=1);

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
     * Can this setter write *into* the children of $entityClassName for the
     * relationship $childFieldName - that is, hand an already-existing child
     * back to the parent to be edited?
     *
     * A relationship declared writeable() (as opposed to linkable()) promises
     * exactly that, and there is no way to honour the promise unless the entity
     * offers somewhere to put it. Answering here rather than at declaration
     * time is deliberate: what counts as "somewhere" is framework-specific, and
     * the setter is the component that knows.
     *
     * Answer conservatively. A false negative turns into a validation error the
     * developer must act on, so only return false when the write is certain to
     * fail.
     *
     * @param string $entityClassName
     * @param string $childFieldName
     * @return bool
     */
    public function supportsChildEditing(string $entityClassName, string $childFieldName): bool;

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
    ): void;

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
