<?php

declare(strict_types=1);

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
    private static array $staticLibrary = [];

    /**
     * @param $resourceDefinition
     * @return \CatLab\Charon\Interfaces\ResourceDefinitionFactory
     */
    public static function getFactoryOrDefaultFactory($resourceDefinition)
    {
        $objectHash = is_string($resourceDefinition) ? $resourceDefinition : spl_object_hash($resourceDefinition);
        if (!isset(self::$staticLibrary[$objectHash])) {
            self::$staticLibrary[$objectHash] = self::createFactoryOrDefaultFactory($resourceDefinition);
        }

        return self::$staticLibrary[$objectHash];
    }

    /**
     * @param $resourceDefinition
     * @return StaticResourceDefinitionFactory
     */
    private static function createFactoryOrDefaultFactory($resourceDefinition): \CatLab\Charon\Interfaces\ResourceDefinitionFactory
    {
        if (!is_subclass_of($resourceDefinition, ResourceDefinitionFactory::class)) {
            return new StaticResourceDefinitionFactory($resourceDefinition);
        }

        if (!is_string($resourceDefinition)) {
            return $resourceDefinition;
        }

        return new $resourceDefinition;
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
