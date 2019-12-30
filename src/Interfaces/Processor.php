<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Models\FilterResults;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Interface Processor
 * @package CatLab\RESTResource\Interfaces
 */
interface Processor
{
    /**
     * @param ResourceTransformer $transformer
     * @param $queryBuilder
     * @param $request
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param FilterResults $filterResults
     * @return mixed
     */
    public function processFilters(
        ResourceTransformer $transformer,
        $queryBuilder,
        $request,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults
    );

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param FilterResults|null $filterResults
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults = null,
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
