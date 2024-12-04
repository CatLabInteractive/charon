<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\Filter;

/**
 *
 */
class FilterCollection extends Collection
{
    private \CatLab\Charon\Interfaces\ResourceDefinition $resourceDefinition;

    public function __construct(ResourceDefinition $resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
    }

    /**
     * @return ResourceDefinition
     */
    public function getResourceDefinition(): \CatLab\Charon\Interfaces\ResourceDefinition
    {
        return $this->resourceDefinition;
    }

    /**
     * @param string $name
     * @return Filter|null
     */
    public function getFromDisplayName(string $name)
    {
        foreach ($this as $v) {
            /** @var Filter $v */
            if ($v->getField()->getDisplayName() === $name) {
                return $v;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return Filter|null
     */
    public function getFromName(string $name)
    {
        foreach ($this as $v) {
            /** @var Filter $v */
            if ($v->getField()->getName() === $name) {
                return $v;
            }
        }

        return null;
    }
}
