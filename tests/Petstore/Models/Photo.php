<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 30/05/16
 * Time: 15:35
 */
namespace Tests\Petstore\Models;


class Photo
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $url;

    /**
     * Photo constructor.
     * @param $id
     * @param $url
     */
    public function __construct($id, $url)
    {
        $this->setId($id);
        $this->setUrl($url);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Photo
     */
    public function setId($id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Photo
     */
    public function setUrl($url): static
    {
        $this->url = $url;
        return $this;
    }
}
