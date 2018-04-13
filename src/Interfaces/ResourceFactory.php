<?php

namespace CatLab\Charon\Interfaces;

/**
 * Interface ResourceFactory
 * @package CatLab\Charon\Interfaces
 */
interface ResourceFactory
{
    /**
     * Create and return a resource.
     * @param ResourceDefinition $resourceDefinition
     * @return RESTResource
     */
    public function createResource(ResourceDefinition $resourceDefinition): RESTResource;

    /**
     * Create and return a ResourceCollection.
     * @return ResourceCollection
     */
    public function createResourceCollection(): ResourceCollection;
}