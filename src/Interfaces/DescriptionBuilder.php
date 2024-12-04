<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\OpenApi\Authentication\Authentication;

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

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title);

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description);

    /**
     * @param string $terms
     * @return $this
     */
    public function setTermsOfService(string $terms);

    /**
     * @param Authentication $authentication
     * @return $this
     */
    public function addAuthentication(Authentication $authentication);
}
