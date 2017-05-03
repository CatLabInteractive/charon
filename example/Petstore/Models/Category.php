<?php

namespace App\Petstore\Models;

/**
 * Class Category
 * @package CatLab\Petstore\Models
 */
class Category
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var Category
     */
    private $parent;

    /**
     * @var Category[]
     */
    private $children = [];

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Category
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Category
     */
    public function getParent(): Category
    {
        return $this->parent;
    }

    /**
     * @param Category $parent
     * @return Category
     */
    public function setParent(Category $parent): Category
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @param Category $child
     * @return $this
     */
    public function addChild(Category $child)
    {
        $child->setParent($this);
        $this->children[] = $child;
        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren()
    {
        return $this->children;
    }
}