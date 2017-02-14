<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\IdentifierCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\ResourceTransformer;

/**
 * Interface InputParser
 * @package CatLab\Charon\Interfaces
 */
interface InputParser
{
    /**
     * Look for identifier input
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @return IdentifierCollection|null
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context
    );

    /**
     * Look for resources
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @return ResourceCollection|null
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context
    );
}