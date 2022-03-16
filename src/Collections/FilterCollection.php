<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\ResourceDefinition;

/**
 *
 */
class FilterCollection extends Collection
{
    /**
     * @var ResourceDefinition
     */
    private $resourceDefinition;

    public function __construct(ResourceDefinition $resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
    }

    /**
     * @return ResourceDefinition
     */
    public function getResourceDefinition()
    {
        return $this->resourceDefinition;
    }
}
