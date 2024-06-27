<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Requirements\Collections\ValidatorCollection;

/**
 * Interface ResourceDefinition
 * @package CatLab\RESTResource
 */
interface ResourceDefinition
{
    /**
     * @return string
     */
    public function getType();

    /**
     * Return the full class name of the expected entities.
     * @return string
     */
    public function getEntityClassName();

    /**
     * Return the entity name in human readable form
     * @param bool|int $plural True for plural, or number for amount expected.
     * @return string
     */
    public function getEntityName($plural = false);

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
     * @return string|null
     */
    public function getDefaultOrder();
}
