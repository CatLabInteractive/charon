<?php

namespace CatLab\Charon\Exceptions;

use CatLab\Requirements\Collections\MessageCollection;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Interfaces\Property;

/**
 *
 */
class LinkRelationshipContainsAttributesException extends PropertyValidationException
{
    /**
     * @param Property $property
     * @param MessageCollection $collection
     * @return PropertyValidationException|void
     */
    public static function make(Property $property, MessageCollection $collection)
    {
        return parent::make($property, $collection);
    }
}
