<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class ResourceFieldCollection
 * @package CatLab\RESTResource\Collections
 */
class ResourceFieldCollection extends Collection
{
    /**
     * @return ResourceFieldCollection
     */
    public function getIdentifiers()
    {
        $out = new self();
        foreach ($this as $v) {
            if ($v instanceof IdentifierField) {
                $out->add($v);
            }
        }
        return $out;
    }

    /**
     * @param string $name
     * @return ResourceField|null
     */
    public function getFromDisplayName(string $name)
    {
        foreach ($this as $v) {
            if ($v->getDisplayName() === $name) {
                return $v;
            }
        }
        return null;
    }

    /**
     * @return array|ResourceFieldCollection
     */
    public function getSortable()
    {
        $out = new self();
        foreach ($this as $v) {
            /** @var $v Field */
            if ($v->isSortable()) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @return array|ResourceFieldCollection
     */
    public function getExpendable()
    {
        $out = new self();
        foreach ($this as $v) {
            /** @var $v Field */
            if ($v->isExpendable()) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * Get all resource fields that should be included in a given context
     * @param Context $context
     * @param CurrentPath|null $path
     * @return array|ResourceFieldCollection
     */
    public function getIncludedInContext(Context $context, CurrentPath $path = null)
    {
        if (!isset($path)) {
            $path = new CurrentPath();
        }

        $out = new self();
        foreach ($this as $v) {
            /** @var $v Field */
            if ($v->shouldInclude($context, $path)) {
                $out[] = $v;
            }
        }

        return $out;
    }
}