<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\IdentifierCollection;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
use CatLab\Charon\Models\Routing\Route;

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
     * @param mixed $request
     * @return IdentifierCollection|null
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context,
        $request = null
    );

    /**
     * Look for resources
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @param mixed $request
     * @return ResourceCollection|null
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinition $resourceDefinition,
        Context $context,
        $request = null
    );

    /**
     * @param DescriptionBuilder $builder
     * @param Route $route
     * @param ResourceParameter $parameter
     * @param ResourceDefinition $resourceDefinition
     * @param mixed $request
     * @return ParameterCollection
     */
    public function getResourceRouteParameters(
        DescriptionBuilder $builder,
        Route $route,
        ResourceParameter $parameter,
        ResourceDefinition $resourceDefinition,
        $request = null
    ) : ParameterCollection;
}