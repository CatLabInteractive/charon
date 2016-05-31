<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\PropertyValues;

/**
 * Class Resource
 * @package CatLab\RESTResource\Contracts
 */
interface RESTResource
{
    /**
     * @return mixed
     */
    public function toArray();

    /**
     * @return PropertyValues
     */
    public function getProperties();

    /**
     * @return ResourceDefinition
     */
    public function getResourceDefinition();

    /**
     * @return mixed
     */
    public function validate();
}