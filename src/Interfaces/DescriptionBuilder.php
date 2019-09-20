<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Models\Routing\Route;

/**
 * Class DescriptionBuilder
 * @package CatLab\RESTResource\Contracts
 */
interface DescriptionBuilder
{
    /**
     * @param Route $route
     * @return $this
     */
    public function addRoute(Route $route);

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return $this
     */
    public function addResourceDefinition(
        ResourceDefinition $resourceDefinition,
        string $action,
        string $cardinality = Cardinality::ONE
    );

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return $this
     */
    public function getResponseSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality);

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return $this
     */
    public function getRelationshipSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality);
}
