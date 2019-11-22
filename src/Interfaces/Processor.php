<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Base\Interfaces\Database\SelectQueryParameters;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Interface Processor
 * @package CatLab\RESTResource\Interfaces
 */
interface Processor
{
    /**
     * @param ResourceTransformer $transformer
     * @param SelectQueryParameters $selectQuery
     * @param $request
     * @param ResourceDefinition $definitionSelectQueryParameters
     * @param Context $context
     * @param int $records
     * @return void
     */
    public function processFilters(
        ResourceTransformer $transformer,
        SelectQueryParameters $selectQuery,
        $request,
        ResourceDefinition $definition,
        Context $context,
        int $records = 10
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    );

    /**
     * @param ResourceTransformer $transformer
     * @param RESTResource $resource
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return
     */
    public function processResource(
        ResourceTransformer $transformer,
        RESTResource $resource,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    );
}
