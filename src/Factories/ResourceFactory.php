<?php

namespace CatLab\Charon\Factories;

use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Interfaces\RESTResource as ResourceInterface;
use CatLab\Charon\Interfaces\ResourceCollection as ResourceCollectionInterface;
use CatLab\Charon\Models\RESTResource;

/**
 * Class ResourceFactory
 * @package CatLab\Charon\Factories
 */
class ResourceFactory implements ResourceFactoryInterface
{
    /**
     * @var string
     */
    protected $resourceClassName;

    /**
     * @var string
     */
    protected $resourceCollectionClassName;

    /**
     * ResourceFactory constructor.
     * @param string|null $resourceClassName
     * @param string|null $resourceCollectionClassName
     */
    public function __construct(
        $resourceClassName = null,
        $resourceCollectionClassName = ResourceCollection::class
    ) {
        if ($resourceClassName === null) {
            $resourceClassName = RESTResource::class;
        }

        if ($resourceCollectionClassName === null) {
            $resourceCollectionClassName = ResourceCollection::class;
        }

        $this->resourceClassName = $resourceClassName;
        $this->resourceCollectionClassName = $resourceCollectionClassName;
    }

    /**
     * Create and return a resource.
     * @param ResourceDefinition $resourceDefinition
     * @return ResourceInterface
     */
    public function createResource(ResourceDefinition $resourceDefinition): ResourceInterface
    {
        return new $this->resourceClassName($resourceDefinition);
    }

    /**
     * Create and return a ResourceCollection.
     * @return ResourceCollection
     */
    public function createResourceCollection(): ResourceCollectionInterface
    {
        return new $this->resourceCollectionClassName();
    }
}