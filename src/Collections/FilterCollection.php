<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Base\Enum\Operator;
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
     * The equality (or, on a searchable field, search) filter on a field.
     * Conditions such as ?owner[neq]=1 are left out on purpose: code that
     * reads a filter's value (an index policy checking "owner is me")
     * predates them and would read "owner is not me" as "owner is me". Use
     * getAllFromDisplayName() to see every filter on the field.
     * @param string $name
     * @return Filter|null
     */
    public function getFromDisplayName(string $name)
    {
        foreach ($this->getAllFromDisplayName($name) as $v) {
            if ($this->isPlainFilter($v)) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Like getFromDisplayName(), by field name.
     * @param string $name
     * @return Filter|null
     */
    public function getFromName(string $name)
    {
        foreach ($this->getAllFromName($name) as $v) {
            if ($this->isPlainFilter($v)) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Every filter on a field, whatever its operator.
     * @param string $name
     * @return Filter[]
     */
    public function getAllFromDisplayName(string $name): array
    {
        $out = [];
        foreach ($this as $v) {
            /** @var Filter $v */
            if ($v->getField()->getDisplayName() === $name) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * Every filter on a field (by field name), whatever its operator.
     * @param string $name
     * @return Filter[]
     */
    public function getAllFromName(string $name): array
    {
        $out = [];
        foreach ($this as $v) {
            /** @var Filter $v */
            if ($v->getField()->getName() === $name) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @param Filter $filter
     * @return bool
     */
    private function isPlainFilter(Filter $filter): bool
    {
        return in_array($filter->getOperator(), [ Operator::EQ, Operator::SEARCH ], true);
    }
}
