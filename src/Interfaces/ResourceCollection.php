<?php

namespace CatLab\Charon\Interfaces;

/**
 * Interface ResourceCollection
 * @package CatLab\Charon\Interfaces
 */
interface ResourceCollection extends SerializableResource
{
    /**
     * Add meta data to the collection.
     * @param $name
     * @param mixed $data
     * @return $this
     */
    public function addMeta($name, $data);

    /**
     * @param $value
     * @return void
     */
    public function add($value);

    /**
     * Get a swagger description of how this collection will be returned.
     * @param $reference
     * @return array
     */
    public function getSwaggerDescription($reference);
}