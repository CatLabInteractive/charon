<?php

declare(strict_types=1);

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
     * @param ResourceDefinitionFactory $resourceDefinition
     * @param Context $context
     * @param mixed $request
     * @return IdentifierCollection|null
     */
    public function getIdentifiers(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    );

    /**
     * Look for resources
     * @param ResourceTransformer $resourceTransformer
     * @param ResourceDefinitionFactory $resourceDefinition
     * @param Context $context
     * @param mixed $request
     * @return ResourceCollection|null
     */
    public function getResources(
        ResourceTransformer $resourceTransformer,
        ResourceDefinitionFactory $resourceDefinition,
        Context $context,
        $request = null
    );

    /**
     * Addapt a Route so that it contains the parameters that this InputParser is
     * looking for. This is then used for automated documentation.
     * @param DescriptionBuilder $builder
     * @param Route $route
     * @param ResourceParameter $parameter
     * @param ResourceDefinition $resourceDefinition
     * @param $action
     * @param mixed $request
     * @return ParameterCollection
     */
    public function getResourceRouteParameters(
        DescriptionBuilder $builder,
        Route $route,
        ResourceParameter $parameter,
        ResourceDefinition $resourceDefinition,
        $action,
        $request = null
    ) : ParameterCollection;
}
