<?php

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
    public static function fromArray($resourceDefinition, array $data)
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