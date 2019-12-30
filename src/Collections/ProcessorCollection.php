<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Base\Interfaces\Database\SelectQueryParameters;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Interfaces\ResourceCollection;
use CatLab\Charon\Models\FilterResults;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class ProcessorCollection
 * @package CatLab\RESTResource\Collections
 */
class ProcessorCollection extends Collection implements Processor
{
    /**
     * @inheritDoc
     */
    public function processFilters(
        ResourceTransformer $transformer,
        $queryBuilder,
        $request,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults
    ) {
        foreach ($this as $processor) {
            /** @var Processor */
            $processor->processFilters($transformer, $queryBuilder, $request, $definition, $context, $filterResults);
        }
    }

    /**
     * @inheritDoc
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults = null,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        foreach ($this as $processor) {
            /** @var Processor */
            $processor->processCollection($transformer, $collection, $definition, $context, $filterResults, $parent, $parentEntity);
        }
    }

    /**
     * @inheritDoc
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
            /** @var Processor */
            $processor->processResource($transformer, $resource, $definition, $context, $parent, $parentEntity);
        }
    }
}
