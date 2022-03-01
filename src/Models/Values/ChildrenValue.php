<?php

namespace CatLab\Charon\Models\Values;

use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class ChildrenValue
 * @package CatLab\RESTResource\Models\Values
 */
class ChildrenValue extends RelationshipValue
{
    /**
     * @var ResourceCollection
     */
    private $children;

    /**
     * @param ResourceCollection $children
     * @return $this
     */
    public function setChildren(ResourceCollection $children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return ResourceCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function getValue()
    {
        $items = $this->children->toArray();
        return $items[ResourceTransformer::RELATIONSHIP_ITEMS];
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        return $this->children->toArray();
    }

    /**
     * @return ResourceCollection
     */
    protected function getChildrenToProcess()
    {
        return $this->children;
    }

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     * @return void
     */
    protected function addChildren(
        ResourceTransformer $transformer,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    ) {
        $propertySetter->addChildren(
            $transformer,
            $entity,
            $this->getField(),
            $childEntities,
            $context
        );
    }

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     * @return void
     */
    protected function editChildren(
        ResourceTransformer $transformer,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    ) {
        $propertySetter->editChildren(
            $transformer,
            $entity,
            $this->getField(),
            $childEntities,
            $context
        );
    }

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param $parent
     * @param PropertyValueCollection $identifiers
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
        return $propertyResolver->getChildByIdentifiers(
            $transformer,
            $this->getField(),
            $parent,
            $identifier,
            $context
        );
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
    ) {
        $propertySetter->removeAllChildrenExcept(
            $transformer,
            $propertyResolver,
            $entity,
            $field,
            $identifiers,
            $context
        );
    }
}
