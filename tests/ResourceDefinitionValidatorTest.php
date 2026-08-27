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

    /**
     * "reply:{context.model}" is a field name carrying childpath parameters;
     * the setter resolves those separately and calls editReply(). Checking the
     * decorated name would look for editReply:{context.model}() and report
     * every parameterised relationship as broken.
     */
    public function testParameterisedFieldNamesAreCheckedAgainstTheirBaseName(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithEditor::class);
        $definition->relationship('child:{context.model}', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * A dotted name sets the child on some other entity entirely, and which one
     * is not knowable from the definition - so it is left alone.
     */
    public function testDottedPathsAreSkipped(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('nested.child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * A relationship that is neither writeable nor linkable promises nothing,
     * so there is nothing to check it against.
     */
    public function testReadOnlyRelationshipsAreIgnored(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->expandable()
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * An entity class that cannot be loaded (a typo, a class from a package
     * that is not installed) has no methods to inspect. Guessing here would
     * turn a definition-time typo into a wall of unrelated relationship
     * errors.
     */
    public function testDefinitionWithAnUnloadableEntityClassIsSkipped(): void
    {
        $definition = new ResourceDefinition('Tests\\NoSuchEntityClassAnywhere');
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->assertSame([], $this->validator()->validate($definition));
    }

    /**
     * Declaring both does not excuse the writeable() half: charon still routes
     * an already-attached child into editChildren(), so the entity still needs
     * to be able to edit.
     */
    public function testARelationshipThatIsBothWriteableAndLinkableIsStillChecked(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->linkable()
            ->writeable()
            ->visible(true, true);

        $this->assertCount(1, $this->validator()->validate($definition));
    }

    /**
     * Every broken relationship has to be reported, not just the first - a
     * definition is usually fixed in one pass.
     */
    public function testEveryBrokenRelationshipIsReported(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition
            ->relationship('first', ResourceDefinition::class)
                ->one()
                ->writeable(true, true)
                ->visible(true, true)

            ->relationship('second', ResourceDefinition::class)
                ->many()
                ->writeable(true, true)
                ->visible(true, true);

        $problems = $this->validator()->validate($definition);

        $this->assertCount(2, $problems);
        $this->assertStringContainsString('"first"', $problems[0]);
        $this->assertStringContainsString('"second"', $problems[1]);
    }

    /**
     * The complement of testParameterisedFieldNamesAreCheckedAgainstTheirBaseName:
     * when the base name really is unsupported, the relationship is reported,
     * and the report names the method the setter will actually look for -
     * editChild(), not editChild:0().
     */
    public function testAParameterisedNameIsReportedUnderItsBaseName(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition->relationship('child:0', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $problems = $this->validator()->validate($definition);

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('editChild()', $problems[0]);
        $this->assertStringNotContainsString('child:0', $problems[0]);
    }

    /**
     * ... and the accepting side of the same, on a name the base-name check
     * actually reaches. (The existing '{context.model}' case never gets that
     * far: it contains a '.', so it is skipped by the dotted-path rule above
     * it.)
     */
    public function testAParameterisedNameIsAcceptedOnItsBaseName(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithEditor::class);
        $definition->relationship('child:0', ResourceDefinition::class)
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

    /**
     * assertValid() is what a build-time test calls, so the exception has to
     * carry every problem - otherwise fixing a definition becomes one
     * test run per relationship.
     */
    public function testAssertValidReportsEveryProblemInOneException(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithoutEditor::class);
        $definition
            ->relationship('first', ResourceDefinition::class)
                ->one()
                ->writeable(true, true)
                ->visible(true, true)

            ->relationship('second', ResourceDefinition::class)
                ->many()
                ->writeable(true, true)
                ->visible(true, true);

        try {
            $this->validator()->assertValid($definition);
            $this->fail('Expected an InvalidResourceDefinition.');
        } catch (InvalidResourceDefinition $e) {
            $this->assertStringContainsString('"first"', $e->getMessage());
            $this->assertStringContainsString('"second"', $e->getMessage());
        }
    }

    public function testAssertValidAcceptsASoundDefinition(): void
    {
        $definition = new ResourceDefinition(ValidatorParentWithEditor::class);
        $definition->relationship('child', ResourceDefinition::class)
            ->one()
            ->writeable(true, true)
            ->visible(true, true);

        $this->validator()->assertValid($definition);
        $this->assertTrue(true, 'assertValid() did not throw for a sound definition.');
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
