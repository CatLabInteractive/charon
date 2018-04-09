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
}