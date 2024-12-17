<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Exceptions\InvalidResourceDefinition;

/**
 * Interface ResourceDefinitionFactory
 * @package CatLab\Charon\Interfaces
 */
interface ResourceDefinitionFactory
{
    /**
     * @param $entity
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    public function getDefault();

    /**
     * @param $entity
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    public function fromEntity($entity);

    /**
     * @param $rawInput
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    public function fromRawInput($rawInput);

    /**
     * @param mixed $content
     * @return ResourceDefinition
     * @throws InvalidResourceDefinition
     */
    public function fromIdentifiers($content);


}
