<?php

declare(strict_types=1);

namespace Tests\Petstore\Models;

/**
 * Class Pet
 * @package CatLab\Petstore\Models
 */
class Pet
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ENDING = 'ending';

    public const STATUS_SOLD = 'sold';

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
    private array $photos = [];

    /**
     * @var Tag[]
     */
    private array $tags = [];

    /**
     * @var string
     */
    private $status;

    private ?\DateTime $someDate = null;

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
    public function setId($id): static
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
    public function setName($name): static
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
    public function setCategory($category): static
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
    public function setPhotos($photos): static
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
    public function setTags($tags): static
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
    public function setStatus($status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSomeDate(): \DateTime
    {
        return $this->someDate;
    }

    /**
     * @param \DateTime $someDate
     * @return Pet
     */
    public function setSomeDate(\DateTime $someDate): static
    {
        $this->someDate = $someDate;
        return $this;
    }
}
