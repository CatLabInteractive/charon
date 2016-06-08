<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Charon\Swagger\SwaggerBuilder;
use CatLab\Requirements\Collections\RequirementCollection;
use CatLab\Requirements\Collections\ValidatorCollection;

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
     * @return string
     */
    public function getUrl();

    /**
     * @return ValidatorCollection
     */
    public function getValidators() : ValidatorCollection;

    /**
     * @param SwaggerBuilder $builder
     * @param string $action
     * @return mixed[]
     */
    public function toSwagger(SwaggerBuilder $builder, $action);
}