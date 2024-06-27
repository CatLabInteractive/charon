<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

/**
 * Class Identifier
 *
 * Represents a set of identifier fields that define a single entity.
 *
 * @package CatLab\Charon\Models
 */
class Identifier extends RESTResource
{
    /**
     *
     */
    public static function fromArray(\CatLab\Charon\Interfaces\ResourceDefinition $resourceDefinition, array $data): self
    {
        $identifier = new self($resourceDefinition);
        foreach ($data as $k => $v) {
            $field = $identifier->getResourceDefinition()->getFields()->getFromDisplayName($k);
            if ($field) {
                $identifier->setProperty($field, $v, true);
            }
        }

        return $identifier;
    }
}
