<?php

namespace CatLab\Charon\Models\Values;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Models\Identifier;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class ChildValue
 * @package CatLab\RESTResource\Models\Values
 */
class ChildValue extends RelationshipValue
{
    /**
     * @var RESTResource
     */
    private $child;

    /**
     * @param RESTResource $child
     * @return $this
     */
    public function setChild(RESTResource $child)
    {
        $this->child = $child;
        return $this;
    }

    /**
     * @return RESTResource
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @return RESTResource
     */
    public function getResource()
    {
        return $this->child;
    }

    /**
     * @return RESTResource[]
     */
    public function getResources()
    {
        return [ $this->child ];
    }

    /**
     * @return array
     */
    public function getValue()
    {
        if ($this->child === null) {
            return null;
        }
        return $this->child->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getTransformedEntityValue(Context $context = null, string $attribute = null)
    {
        if ($this->child === null) {
            return null;
        }

        if ($attribute === null) {
            return $this->child->getProperties()->transformToEntityValuesMap($context);
        } else {
            // For performance reasons, only process the field we actually want.
            $field = $this->child->getProperties()->getFromName($attribute);
            if (!$field) {
                return null;
            }

            return $field->getTransformedEntityValue($context);
        }
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        if ($this->child === null) {
            return null;
        }
        return $this->child->toArray();
    }

    /**
     * @return RESTResource[]
     */
    public function getChildren()
    {
        return $this->getChildrenToProcess();
    }

    /**
     * @return RESTResource[]
     */
    protected function getChildrenToProcess()
    {
        return [ $this->child ];
    }

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     */
    protected function addChildren(
        ResourceTransformer $transformer,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    )
    {
        if (count($childEntities) > 0) {
            $propertySetter->setChild($transformer, $entity, $this->getField(), $childEntities[0], $context);
        }
    }

    /**
     * Add a child to a collection
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     * @return
     */
    protected function editChildren(ResourceTransformer $transformer, PropertySetter $propertySetter, $entity, RelationshipField $field, array $childEntities, Context $context)
    {
        if (count($childEntities) > 0) {
            $propertySetter->editChildren($transformer, $entity, $this->getField(), $childEntities, $context);
        }
    }

    /**
     * Look for a child with given identifiers
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param $parent
     * @param Identifier $identifier
     * @param Context $context
     * @return mixed
     */
    protected function getChildByIdentifiers(
        ResourceTransformer $transformer,
        PropertyResolver $propertyResolver,
        &$parent,
        Identifier $identifier,
        Context $context
    ) {
        $childEntity = $propertyResolver->resolveProperty($transformer, $parent, $this->getField(), $context);

        if (
            !$childEntity ||
            !$propertyResolver->doesResourceRepresentEntity(
                $transformer,
                $childEntity,
                $this->child,
                $context
            )
        ) {
            $childEntity = null;
        }

        return $childEntity;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param PropertyValueCollection[] $identifiers
     * @param Context $context
     * @return mixed
     */
    protected function removeAllChildrenExcept(
        ResourceTransformer $transformer,
        PropertyResolver $propertyResolver,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $identifiers,
        Context $context
    )
    {
        // Only one value allowed, so if $identifiers is empty, clear value
        if (empty($identifiers)) {
            $propertySetter->clearChild($transformer, $entity, $field, $context);
        }
    }
}
