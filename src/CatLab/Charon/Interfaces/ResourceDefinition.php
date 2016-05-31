<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Charon\Models\SwaggerBuilder;

/**
 * Interface ResourceDefinition
 * @package CatLab\RESTResource
 */
interface ResourceDefinition
{
    /**
     * Return the full class name of the expected entities.
     * @return string
     */
    public function getEntityClassName();

    /**
     * @return ResourceFieldCollection
     */
    public function getFields();

    /**
     * @param SwaggerBuilder $builder
     * @param string $action
     * @return mixed[]
     */
    public function toSwagger(SwaggerBuilder $builder, $action);

    /**
     * @return string
     */
    public function getUrl();
}