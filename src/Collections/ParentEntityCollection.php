<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

/**
 * Class ParentEntities
 * @package CatLab\RESTResource\Collections
 */
class ParentEntityCollection implements \Countable
{
    private array $entities = [];

    public function push($entity): void
    {
        $this->entities[] = $entity;
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

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->entities);
    }
}
