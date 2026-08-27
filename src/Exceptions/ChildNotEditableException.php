<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

/**
 * Thrown when a relationship declared writeable() hands an existing child back
 * to its parent to be edited, and the parent entity has nowhere to put it.
 *
 * This is nearly always a resource-definition mistake rather than a bad
 * request: writeable() on a relationship means "create and edit children from
 * here", which is a stronger claim than linkable() ("point at an existing
 * one"). Relationships that only ever need to point at something should
 * declare linkable() alone - it already makes the field writeable.
 *
 * @package CatLab\Charon\Exceptions
 */
class ChildNotEditableException extends ResourceException
{
    public static function create(
        string $entityClassName,
        string $childFieldName,
        string $propertySetterClassName
    ): CharonException {
        return self::makeTranslatable(
            'Relationship "%s" is declared writeable(), so %s was asked to edit an existing child ' .
            'in %s, but %s provides no way to do that. Either declare the relationship linkable() ' .
            'instead (linkable() already makes the field writeable, it just links rather than ' .
            'edits), or give %s an edit%s() method.',
            [
                $childFieldName,
                $propertySetterClassName,
                $entityClassName,
                $entityClassName,
                $entityClassName,
                ucfirst($childFieldName),
            ]
        );
    }
}
