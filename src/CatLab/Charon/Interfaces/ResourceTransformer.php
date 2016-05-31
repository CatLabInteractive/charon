<?php

namespace CatLab\Charon\Interfaces;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Interface ResourceTransformer
 * @package CatLab\RESTResource\Contracts
 */
interface ResourceTransformer
{
    const RELATIONSHIP_LINK = 'link';
    const RELATIONSHIP_ITEMS = 'items';
    const SORT_PARAMETER = 'sort';
    const LIMIT_PARAMETER = 'records';

    /**
     * @return mixed
     */
    public function getParentEntity();

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param $entities
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceCollection
     */
    public function toResources(
        $resourceDefinition,
        $entities,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : ResourceCollection;

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param $entity
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return RESTResource
     */
    public function toResource(
        $resourceDefinition,
        $entity,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : RESTResource;

    /**
     * @param $resourceDefinition
     * @param $body
     * @param Context $context
     * @return RESTResource
     */
    public function fromArray(
        $resourceDefinition,
        array $body,
        Context $context
    ) : RESTResource;

    /**
     * @param RESTResource $resource
     * @param $resourceDefinition
     * @param EntityFactory $factory
     * @param Context $context
     * @param mixed|null $entity
     * @return mixed $entity
     */
    public function toEntity(
        RESTResource $resource,
        $resourceDefinition,
        EntityFactory $factory,
        Context $context,
        $entity = null
    );

    /**
     * @param $request
     * @param $resourceDefinition
     * @param Context $context
     * @param int $records
     * @return SelectQueryParameters
     */
    public function getFilters($request, $resourceDefinition, Context $context, int $records = null);

    /**
     * @return PropertyResolver
     */
    public function getPropertyResolver() : PropertyResolver;

    /**
     * @return PropertySetter
     */
    public function getPropertySetter() : PropertySetter;
}