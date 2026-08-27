<?php

declare(strict_types=1);

namespace CatLab\Charon\Validation;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\InvalidResourceDefinition;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Properties\RelationshipField;

/**
 * Checks that a resource definition's relationships promise only what the
 * entities behind them can deliver.
 *
 * The mistake this exists to catch: declaring a relationship writeable() when
 * linkable() was meant. Both make the field writeable - linkable() calls
 * writeable() on the underlying field itself - so the declaration looks
 * harmless and the resource reads back fine. It only fails on a write that
 * carries the relationship, and it fails with an error about a "property" that
 * gives no hint a relationship is involved. Running this over every definition
 * in a test turns that into a failure at build time with the fix in the message.
 *
 * @package CatLab\Charon\Validation
 */
class ResourceDefinitionValidator
{
    public function __construct(private readonly PropertySetter $propertySetter)
    {
    }

    /**
     * @param ResourceDefinition $definition
     * @return string[] one message per problem; empty when the definition is sound
     */
    public function validate(ResourceDefinition $definition): array
    {
        $entityClassName = $definition->getEntityClassName();

        // Nothing to check a promise against.
        if (!$entityClassName || !class_exists($entityClassName)) {
            return [];
        }

        $problems = [];

        foreach ($definition->getFields() as $field) {
            if (!$field instanceof RelationshipField) {
                continue;
            }

            // Only child *editing* is checked. Creating a child has framework
            // fallbacks that cannot be recognised from a class name alone (an
            // Eloquent BelongsToMany, for one), and a false positive here would
            // be a build failure on working code - so that half stays
            // unchecked rather than guessed at.
            if (!$this->editsChildren($field)) {
                continue;
            }

            if ($this->propertySetter->supportsChildEditing($entityClassName, $field->getName())) {
                continue;
            }

            $problems[] = sprintf(
                'Relationship "%s" on %s is declared writeable(), which promises that an existing ' .
                '%s can be edited through it, but %s offers no edit%s() method and %s cannot edit ' .
                'its children any other way. Declare it linkable() instead if it should only point ' .
                'at an existing record - linkable() already makes the field writeable.',
                $field->getName(),
                $definition::class,
                $entityClassName,
                $entityClassName,
                ucfirst($field->getName()),
                $this->propertySetter::class
            );
        }

        return $problems;
    }

    /**
     * @param ResourceDefinition $definition
     * @throws InvalidResourceDefinition
     */
    public function assertValid(ResourceDefinition $definition): void
    {
        $problems = $this->validate($definition);
        if ($problems === []) {
            return;
        }

        throw InvalidResourceDefinition::makeTranslatable(implode("\n", $problems));
    }

    private function editsChildren(RelationshipField $field): bool
    {
        foreach ([ Action::CREATE, Action::EDIT ] as $action) {
            if ($field->canCreateNewChildren(new Context($action))) {
                return true;
            }
        }

        return false;
    }
}
