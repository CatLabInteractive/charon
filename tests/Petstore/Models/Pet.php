<?php

namespace Tests\Petstore\Models;

/**
 * Class Pet
 * @package CatLab\Petstore\Models
 */
class Pet
{
    const STATUS_AVAILABLE = 'available';
    const STATUS_ENDING = 'ending';
    const STATUS_SOLD = 'sold';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var string[]
     */
    private $photos = [];

    /**
     * @var Tag[]
     */
    private $tags = [];

    /**
     * @var string
     */
    private $status;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Pet
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
     * @return Pet
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return Pet
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * @param \string[] $photos
     * @return Pet
     */
    public function setPhotos($photos)
    {
        $this->photos = $photos;
        return $this;
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag[] $tags
     * @return Pet
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Pet
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}