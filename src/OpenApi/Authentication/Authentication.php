<?php

declare(strict_types=1);

namespace CatLab\Charon\OpenApi\Authentication;

/**
 * Class Authentication
 * @package CatLab\Charon\Models\Swagger
 */
abstract class Authentication
{
    protected string $name;

    protected string $type;

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
