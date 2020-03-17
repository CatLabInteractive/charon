<?php

namespace CatLab\Charon\OpenApi\Authentication;

/**
 * Class Authentication
 * @package CatLab\Charon\Models\Swagger
 */
abstract class Authentication
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * Authentication constructor.
     * @param string $name
     * @param string $type
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    abstract public function toArray();
}
