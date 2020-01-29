<?php


namespace CatLab\Charon\Models;

use CatLab\Charon\Exceptions\InvalidResourceDefinition;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceDefinitionFactory;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Library\ResourceDefinitionLibrary;

/**
 * Class StaticResourceDefinitionFactory
 *
 * This resource definition factory always returns the same resource definition.
 *
 * @package CatLab\Charon\Models
 */
class StaticResourceDefinitionFactory implements \CatLab\Charon\Interfaces\ResourceDefinitionFactory
{
    /**
     * @param $resourceDefinition
     * @return \CatLab\Charon\Interfaces\ResourceDefinitionFactory
     */
    public static function getFactoryOrDefaultFactory($resourceDefinition)
    {
        if (!is_subclass_of($resourceDefinition, ResourceDefinitionFactory::class)) {
            return new StaticResourceDefinitionFactory($resourceDefinition);
        } else {
            if (!is_string($resourceDefinition)) {
                return $resourceDefinition;
            } else {
                return new $resourceDefinition;
            }
        }
    }

    /**
     * @var ResourceDefinition|string
     */
    private $resourceDefinition;

    /**
     * StaticResourceDefinitionFactory constructor.
     * @param $resourceDefinition
     */
    public function __construct($resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
    }

    /**
     * @inheritDoc
     */
    public function fromEntity($entity)
    {
        return ResourceDefinitionLibrary::make($this->resourceDefinition);
    }

    /**
     * @inheritDoc
     */
    public function fromRawInput($rawInput)
    {
        return ResourceDefinitionLibrary::make($this->resourceDefinition);
    }

    /**
     * @inheritDoc
     */
    public function getDefault()
    {
        return ResourceDefinitionLibrary::make($this->resourceDefinition);
    }

    /**
     * @inheritDoc
     */
    public function fromResource(RESTResource $resource)
    {
        return ResourceDefinitionLibrary::make($this->resourceDefinition);
    }

    /**
     * @inheritDoc
     */
    public function fromIdentifiers($content)
    {
        return ResourceDefinitionLibrary::make($this->resourceDefinition);
    }
}
