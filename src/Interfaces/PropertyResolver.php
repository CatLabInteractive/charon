<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Exceptions\ValueUndefined;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\RESTResource;

/**
 * Interface PropertyResolver
 * @package CatLab\RESTResource\Contracts
 */
interface PropertyResolver
{
    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param Field $field
     * @param Context $context
     * @return mixed
     */
    public function resolveProperty(
        ResourceTransformer $transformer,
        $entity,
        Field $field,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param Context $context
     */
    public function resolveManyRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param Context $context
     * @return RESTResource
     */
    public function resolveOneRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param string $path
     * @param Context $context
     * @return string
     */
    public function resolvePathParameters(
        ResourceTransformer $transformer,
        $entity,
        $path,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param &$input
     * @param Field $field
     * @param Context $context
     * @return mixed
     * @throws ValueUndefined
     */
    public function resolvePropertyInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    );

    /**
     * Check if input contains data.
     * @param ResourceTransformer $transformer
     * @param $input
     * @param Field $field
     * @param Context $context
     * @return bool
     */
    public function hasPropertyInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    ) : bool;

    /**
     * Check if relationship data exists in input.
     * @param ResourceTransformer $transformer
     * @param $input
     * @param RelationshipField $field
     * @param Context $context
     * @return bool
     */
    public function hasRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    ) : bool;

    /**
     * @param ResourceTransformer $transformer
     * @param mixed &$input,
     * @param RelationshipField $field
     * @param Context $context
     * @return ResourceCollection
     */
    public function resolveManyRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    ) : ResourceCollection;

    /**
     * @param ResourceTransformer $transformer
     * @param mixed &$input,
     * @param RelationshipField $field
     * @param Context $context
     * @return RESTResource
     */
    public function resolveOneRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RESTResource $resource
     * @param Context $context
     * @return bool
     */
    public function doesResourceRepresentEntity(
        ResourceTransformer $transformer,
        $entity,
        RESTResource $resource,
        Context $context
    ) : bool;
}
