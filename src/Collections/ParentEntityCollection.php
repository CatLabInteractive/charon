<?php

namespace CatLab\Charon\Collections;

/**
 * Class ParentEntities
 * @package CatLab\RESTResource\Collections
 */
class ParentEntityCollection
{
    private $entities = [];

    public function push($entity)
    {
        array_push($this->entities, $entity);
    }

    public function pop()
    {
        return array_pop($this->entities);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        if (count($this->entities) > 1) {
            return $this->entities[count($this->entities) - 2];
        }
        return null;
    }
}