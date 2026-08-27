<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Requirements\Exceptions\ResourceValidationException;

/**
 * RelationshipValue::validate() has three quite different modes depending on
 * what the relationship declared, and picking the wrong one either lets bad
 * input through or rejects good input:
 *
 *  - can create children  -> validate each child as a full resource
 *  - can only link        -> validate each child as an identifier, and reject
 *                            any child that carries more than identifiers
 *  - can do both          -> try the link reading first, fall back to the full
 *                            one
 *
 * ValidatorTest already covers the writeable-only mode (via PetDefinition's
 * 'photos'); these cover the linking modes and the required-relationship
 * requirement.
 */
final class RelationshipValidationTest extends BaseTest
{
    /**
     * @return string[] the validation messages, as rendered
     */
    private function validationMessages(string $definition, array $body, string $action = Action::CREATE): array
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context($action);

        $resource = $transformer->fromArray($definition, $body, $context);

        try {
            $resource->validate($context);
        } catch (ResourceValidationException $e) {
            return array_map(fn ($message): string => (string) $message, iterator_to_array($e->getMessages()));
        }

        return [];
    }

    public function testAnIdentifierOnlyChildIsAValidLink(): void
    {
        $this->assertSame(
            [],
            $this->validationMessages(
                LinkOnlyParentDefinition::class,
                [ 'items' => [ 'items' => [ [ 'id' => 1 ] ] ] ]
            )
        );
    }

    /**
     * A linkable relationship points at something that already exists, so a
     * child without an identifier is meaningless - and it must be reported
     * rather than quietly ignored, because the alternative is a write that
     * looks accepted and links nothing.
     */
    public function testALinkableChildWithoutAnIdentifierIsRejected(): void
    {
        $messages = $this->validationMessages(
            LinkOnlyParentDefinition::class,
            [ 'items' => [ 'items' => [ [ ] ] ] ]
        );

        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('id', implode("\n", $messages));
    }

    /**
     * The other half of the same rule: a linkable-only relationship may not be
     * used to smuggle attribute changes into the linked record. Accepting
     * {"id": 1, "name": "..."} there would read as an edit and silently not be
     * one.
     */
    public function testALinkableChildCarryingAttributesIsRejected(): void
    {
        $messages = $this->validationMessages(
            LinkOnlyParentDefinition::class,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'name' => 'renamed' ] ] ] ]
        );

        $this->assertStringContainsString(
            'Linkable resources may not contain any other attributes',
            implode("\n", $messages)
        );
    }

    /**
     * A relationship that can both create and link takes the link reading when
     * the child is identifier-only, so the child's own required fields must not
     * be demanded of it.
     */
    public function testAChildThatCanBeReadAsALinkSkipsFullValidation(): void
    {
        $this->assertSame(
            [],
            $this->validationMessages(
                WriteableAndLinkableParentDefinition::class,
                [ 'items' => [ 'items' => [ [ 'id' => 1 ] ] ] ]
            )
        );
    }

    /**
     * ... and when it cannot be read as a link, validation falls through to the
     * full resource reading, which does demand the child's required fields.
     * (Failing to fall through is how required fields on nested children went
     * unchecked.)
     */
    public function testAChildThatIsNotALinkStillGetsFullValidation(): void
    {
        $messages = $this->validationMessages(
            WriteableAndLinkableParentDefinition::class,
            [ 'items' => [ 'items' => [ [ 'description' => 'no name' ] ] ] ]
        );

        $this->assertNotEmpty($messages, 'A non-link child must be validated as a full resource.');
        $this->assertStringContainsString('name', implode("\n", $messages));
    }

    public function testAFullChildSatisfyingItsRequirementsPasses(): void
    {
        $this->assertSame(
            [],
            $this->validationMessages(
                WriteableAndLinkableParentDefinition::class,
                [ 'items' => [ 'items' => [ [ 'name' => 'a name' ] ] ] ]
            )
        );
    }

    /**
     * ->required() on a relationship installs RelationshipExists, whose whole
     * job is to produce an error naming the relationship when nothing was
     * provided for it.
     */
    public function testARequiredRelationshipMustBeProvided(): void
    {
        $messages = $this->validationMessages(RequiredRelationshipParentDefinition::class, []);

        $this->assertStringContainsString('required relationship', implode("\n", $messages));
    }

    public function testARequiredRelationshipIsSatisfiedByALink(): void
    {
        $this->assertSame(
            [],
            $this->validationMessages(
                RequiredRelationshipParentDefinition::class,
                [ 'child' => [ 'id' => 1 ] ]
            )
        );
    }
}

class ValidationChildEntity
{
}

class ValidationParentEntity
{
}

class ValidationChildDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ValidationChildEntity::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->required()
                ->writeable()
                ->visible()

            ->field('description')
                ->writeable()
                ->visible()
        ;
    }
}

class LinkOnlyParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ValidationParentEntity::class);

        $this
            ->relationship('items', ValidationChildDefinition::class)
                ->many()
                ->linkable()
                ->visible()
        ;
    }
}

class WriteableAndLinkableParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ValidationParentEntity::class);

        $this
            ->relationship('items', ValidationChildDefinition::class)
                ->many()
                ->linkable()
                ->writeable()
                ->visible()
        ;
    }
}

class RequiredRelationshipParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ValidationParentEntity::class);

        $this
            ->relationship('child', ValidationChildDefinition::class)
                ->one()
                ->linkable()
                ->required()
                ->visible()
        ;
    }
}
