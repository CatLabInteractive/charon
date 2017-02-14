<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

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
     * @param RelationshipValue $field
     * @param Context $context
     * @return ResourceCollection
     */
    public function resolveManyRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipValue $field,
        Context $context
    ) : ResourceCollection;

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipValue $field
     * @param Context $context
     * @return RESTResource
     */
    public function resolveOneRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipValue $field,
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
     */
    public function resolvePropertyInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    );

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
     * @param RelationshipField $field
     * @param mixed $parentEntity
     * @param PropertyValueCollection $identifiers
     * @param Context $context
     * @return mixed
     */
    public function getChildByIdentifiers(
        ResourceTransformer $transformer,
        RelationshipField $field,
        $parentEntity,
        PropertyValueCollection $identifiers,
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

    /**
     * @param $request
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getParameterFromRequest(
        $request,
        string $key,
        $default = null
    );

    /**
     * @param Field $field
     * @return mixed
     */
    public function getQualifiedName(Field $field);

    /**
     * @param ResourceTransformer $transformer
     * @param $entityCollection
     * @param RelationshipField $field
     * @param Context $context
     * @return void
     */
    public function eagerLoadRelationship(
        ResourceTransformer $transformer,
        $entityCollection,
        RelationshipField $field,
        Context $context
    );
}