<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Collections\PropertyValueCollection;

/**
 * Class Resource
 * @package CatLab\RESTResource\Contracts
 */
interface RESTResource extends SerializableResource
{
    /**
     * @return mixed
     */
    public function toArray();

    /**
     * @return PropertyValueCollection
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