<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Exceptions\InvalidResourceDefinition;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\SimpleResolvers\SimplePropertySetter;
use CatLab\Charon\Validation\ResourceDefinitionValidator;

/**
 * A relationship declared writeable() promises its children can be edited
 * through the parent. Nothing used to check that promise against the entity,
 * and because linkable() makes the field writeable too, declaring the wrong one
 * looked identical until a write carrying the relationship blew up.
 */
final class ResourceDefinitionValidatorTest extends BaseTest
{
    private function validator(): ResourceDefinitionValidator
    {
        return new ResourceDefinitionValidator(new SimplePropertySetter());
    }

    public function testLinkableOnlyRelationshipIsAccepted(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->linkable()
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    public function testWriteableRelationshipOnAnEntityThatCannotEditChildrenIsReported(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $problems = $this->validator()->validate($definition);

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('"child"', $problems[0]);
        $this->assertStringContainsString(ValidatorParentWithoutEditor::class, $problems[0]);
        // The message has to carry the fix, not just the complaint.
        $this->assertStringContainsString('linkable()', $problems[0]);
        $this->assertStringContainsString('editChild()', $problems[0]);
    }

    public function testWriteableRelationshipIsAcceptedWhenTheEntityCanEditChildren(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * writeable(false, true) is edit-only, which is still an edit promise.
     */
    public function testEditOnlyWriteableIsStillChecked(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->many()
            ->writeable(false, true)
            ->linkable(true, false)
            ->visible(false, true);

        $this->assertCount(1, $this->validator()->validate($definition));
    }

    public function testPlainFieldsAreIgnored(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->field('name')->writeable(true, true)->visible();

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * A definition with no entity class has nothing to check a promise against.
     */
    public function testDefinitionWithoutAnEntityClassIsSkipped(): void
    {
        $definition = new ResourceDefinition(null);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    public function testAssertValidThrows(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->expectException(InvalidResourceDefinition::class);
        $this->validator()->assertValid($definition);
    }
}

class ValidatorParentWithoutEditor
{
}

class ValidatorParentWithEditor
{
    public function editChild($children)
    {
    }
}
