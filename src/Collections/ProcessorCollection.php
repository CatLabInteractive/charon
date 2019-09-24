<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class ProcessorCollection
 * @package CatLab\RESTResource\Collections
 */
class ProcessorCollection extends Collection implements Processor
{
    /**
     * @param ResourceTransformer $transformer
     * @param SelectQueryParameters $selectQuery
     * @param $request
     * @param ResourceDefinition $definition
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
    ) {
        foreach ($this as $processor) {
            /** @var Processor */
            $processor->processFilters($transformer, $selectQuery, $request, $definition, $context, $records);
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        foreach ($this as $processor) {
            /** @var Processor $processor */
            $processor->processCollection($transformer, $collection, $definition, $context, $parent, $parentEntity);
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param RESTResource $resource
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param mixed $parentEntity
     */
    public function processResource(
        ResourceTransformer $transformer,
        RESTResource $resource,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        foreach ($this as $processor) {
            /** @var Processor $processor */
            $processor->processResource($transformer, $resource, $definition, $context, $parent);
        }
    }
}
