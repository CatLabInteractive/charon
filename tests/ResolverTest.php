<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Exceptions\ValueUndefined;
use CatLab\Charon\Exceptions\VariableNotFoundInContext;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\ResourceDefinition;
use InvalidArgumentException;
use Tests\Models\MockPropertyResolver;

/**
 * The resolvers turn a field name into an actual read or write on an entity.
 * Field names are a small language: '.' walks down into another entity, ':'
 * passes parameters to the getter, and '{...}' interpolates a value out of the
 * context, the model or the parent ('reply:{context.model}').
 *
 * These tests cover the reading and writing side of that language, and the
 * edit-vs-create decision PropertyResolver makes for child input.
 */
final class ResolverTest extends BaseTest
{
    /***********************************************************************
     * Reading: ResolverBase::resolveChildPath / getValueFromEntity
     ***********************************************************************/

    public function testDottedFieldNameWalksIntoTheChildEntity(): void
    {
        $entity = new ResolverEntity();
        $entity->setProfile(new ResolverProfile('deadpan'));

        $resource = $this->getResourceTransformer()->toResource(
            ResolverReadDefinition::class,
            $entity,
            new Context(Action::VIEW)
        );

        $this->assertSame('deadpan', $resource->toArray()['nickname']);
    }

    /**
     * A dotted path that dead-ends halfway must resolve to null, not explode.
     */
    public function testDottedFieldNameResolvesToNullWhenAnIntermediateEntityIsMissing(): void
    {
        $entity = new ResolverEntity();

        $resource = $this->getResourceTransformer()->toResource(
            ResolverReadDefinition::class,
            $entity,
            new Context(Action::VIEW)
        );

        $this->assertNull($resource->toArray()['nickname']);
    }

    /**
     * getValueFromEntity() tries get<Name>(), then is<Name>(), then a public
     * property. All three shapes have to work - a boolean accessor named
     * isPublished() is idiomatic PHP and must not need a definition-side alias.
     */
    public function testIsPrefixedGetterAndPublicPropertyAreBothUsable(): void
    {
        $entity = new ResolverEntity();

        $resource = $this->getResourceTransformer()->toResource(
            ResolverReadDefinition::class,
            $entity,
            new Context(Action::VIEW)
        );

        $array = $resource->toArray();
        $this->assertTrue($array['published'], 'isPublished() should be found for a field named "published".');
        $this->assertSame('a-slug', $array['slug'], 'A public property should be readable when there is no getter.');
    }

    /**
     * A ':' parameter interpolated from the context is the mechanism behind
     * names like 'reply:{context.model}'. When the context does not carry the
     * parameter, the failure has to name the field - a silent null here would
     * turn into a wrong (and possibly leaky) result further down.
     */
    public function testMissingContextParameterForAParameterisedFieldNameThrows(): void
    {
        $entity = new ResolverEntity();

        $this->expectException(VariableNotFoundInContext::class);

        $this->getResourceTransformer()->toResource(
            ResolverParameterDefinition::class,
            $entity,
            new Context(Action::VIEW)
        );
    }

    public function testContextParameterIsPassedToTheGetter(): void
    {
        $entity = new ResolverEntity();

        $context = new Context(Action::VIEW);
        $context->setParameter('suffix', '!!');

        $resource = $this->getResourceTransformer()->toResource(
            ResolverParameterDefinition::class,
            $entity,
            $context
        );

        $this->assertSame('shout!!', $resource->toArray()['shout']);
    }

    /**
     * '{context.x?}' marks the parameter optional: a missing value becomes null
     * instead of an exception.
     */
    public function testOptionalContextParameterResolvesToNullInsteadOfThrowing(): void
    {
        $entity = new ResolverEntity();

        $resource = $this->getResourceTransformer()->toResource(
            ResolverOptionalParameterDefinition::class,
            $entity,
            new Context(Action::VIEW)
        );

        $this->assertSame('shout', $resource->toArray()['shout'], 'A missing optional parameter should arrive as null.');
    }

    /**
     * resolvePathParameters() is what builds relationship URLs. Only 'model',
     * 'context' and 'parent' are meaningful namespaces; a typo has to be loud,
     * because the alternative is a URL with a hole in it.
     */
    public function testUnknownParameterNamespaceIsRejected(): void
    {
        $transformer = $this->getResourceTransformer();

        $this->expectException(InvalidArgumentException::class);

        $transformer->getPropertyResolver()->resolvePathParameters(
            $transformer,
            new ResolverEntity(),
            '/url/{entity.id}',
            new Context(Action::VIEW)
        );
    }

    /**
     * splitPathParameters() must not split inside '{...}': a '.' there belongs
     * to the parameter, not to the path.
     */
    public function testPathSeparatorsInsideAParameterAreNotPathSeparators(): void
    {
        $resolver = new MockPropertyResolver();

        $this->assertEquals(
            [ 'reply:{context.model}' ],
            $resolver->splitPathParameters('reply:{context.model}')
        );

        $this->assertEquals(
            [ 'a', 'reply:{context.model.id}', 'b' ],
            $resolver->splitPathParameters('a.reply:{context.model.id}.b')
        );
    }

    /***********************************************************************
     * Reading input: PropertyResolver::resolvePropertyInput
     ***********************************************************************/

    /**
     * A display name may itself be dotted, which nests the value in the
     * payload. Both halves have to be walked, and a missing branch must be
     * treated as "not provided" rather than as null.
     */
    public function testDottedDisplayNameReadsANestedInputValue(): void
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(
            ResolverNestedInputDefinition::class,
            [ 'meta' => [ 'nickname' => 'deadpan' ] ],
            $context
        );

        $this->assertSame('deadpan', $resource->getProperties()->getFromName('nickname')->getValue());
    }

    public function testMissingNestedInputBranchLeavesThePropertyUnset(): void
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(ResolverNestedInputDefinition::class, [], $context);

        $this->assertNull($resource->getProperties()->getFromName('nickname'));
    }

    public function testResolvePropertyInputThrowsValueUndefinedForAMissingKey(): void
    {
        $transformer = $this->getResourceTransformer();
        $definition = new ResolverNestedInputDefinition();
        $input = [ 'meta' => [] ];

        $this->expectException(ValueUndefined::class);

        $transformer->getPropertyResolver()->resolvePropertyInput(
            $transformer,
            $input,
            $definition->getFields()->getFromName('nickname'),
            new Context(Action::CREATE)
        );
    }

    /***********************************************************************
     * Edit vs create: PropertyResolver::getInputChildContext
     ***********************************************************************/

    /**
     * A child in the payload carrying an identifier is an edit of an existing
     * record, one without is a creation, and the child context's action has to
     * say so - it is what decides which of the child's fields are writeable at
     * all. (This broke once when the input never reached hasInputIdentifier(),
     * making every child look new.)
     */
    public function testChildWithAnIdentifierIsParsedInAnEditContext(): void
    {
        $resource = $this->getResourceTransformer()->fromArray(
            ResolverEditDetectionDefinition::class,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'name' => 'a', 'editOnly' => 'yes' ] ] ] ],
            new Context(Action::EDIT)
        );

        $child = $resource->getProperties()->getFromName('items')->getChildren()[0];

        $this->assertSame('yes', $child->getProperties()->getFromName('editOnly')->getValue());
    }

    public function testChildWithoutAnIdentifierIsParsedInACreateContext(): void
    {
        $resource = $this->getResourceTransformer()->fromArray(
            ResolverEditDetectionDefinition::class,
            [ 'items' => [ 'items' => [ [ 'name' => 'a', 'editOnly' => 'yes' ] ] ] ],
            new Context(Action::EDIT)
        );

        $child = $resource->getProperties()->getFromName('items')->getChildren()[0];

        $this->assertNull(
            $child->getProperties()->getFromName('editOnly'),
            'A child without an identifier is a creation; edit-only fields must not be writeable.'
        );
    }

    /**
     * Same decision for a cardinality-ONE relationship, which reaches
     * getInputChildContext() through resolveOneRelationshipInput().
     */
    public function testOneRelationshipChildWithAnIdentifierIsParsedInAnEditContext(): void
    {
        $resource = $this->getResourceTransformer()->fromArray(
            ResolverEditDetectionDefinition::class,
            [ 'child' => [ 'id' => 1, 'editOnly' => 'yes' ] ],
            new Context(Action::EDIT)
        );

        $child = $resource->getProperties()->getFromName('child')->getChild();

        $this->assertSame('yes', $child->getProperties()->getFromName('editOnly')->getValue());
    }

    /**
     * In a CREATE context every child is new, whatever identifier it carries -
     * the parent itself does not exist yet.
     */
    public function testEveryChildIsACreationInsideACreateContext(): void
    {
        $resource = $this->getResourceTransformer()->fromArray(
            ResolverEditDetectionDefinition::class,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'editOnly' => 'yes' ] ] ] ],
            new Context(Action::CREATE)
        );

        $child = $resource->getProperties()->getFromName('items')->getChildren()[0];

        $this->assertNull($child->getProperties()->getFromName('editOnly'));
    }

    /***********************************************************************
     * Writing: PropertySetter
     ***********************************************************************/

    /**
     * A dotted field name writes to the nested entity, not to the root one -
     * and PropertySetter deliberately does not create the intermediate entity.
     */
    public function testDottedFieldNameWritesToTheNestedEntity(): void
    {
        $entity = new ResolverEntity();
        $profile = new ResolverProfile('old');
        $entity->setProfile($profile);

        $this->writeInto(ResolverWriteDefinition::class, $entity, [ 'nickname' => 'new' ]);

        $this->assertSame('new', $profile->getNickname());
    }

    /**
     * An entity with neither a setter nor a matching property cannot take the
     * value, and has to say so rather than dropping it.
     */
    public function testWritingAFieldTheEntityCannotTakeThrows(): void
    {
        $this->expectException(InvalidPropertyException::class);

        $this->writeInto(ResolverWriteDefinition::class, new ResolverEntity(), [ 'unsettable' => 'x' ]);
    }

    /**
     * The hasAttribute() fallback exists for ORM entities (Eloquent models)
     * whose columns are neither declared properties nor have setters.
     */
    public function testHasAttributeFallbackIsUsedWhenThereIsNoSetterOrProperty(): void
    {
        $entity = new ResolverAttributeBag();

        $this->writeInto(ResolverAttributeBagDefinition::class, $entity, [ 'title' => 'from input' ]);

        $this->assertSame('from input', $entity->title);
    }

    /**
     * @param string $definition
     * @param mixed $entity
     * @param array $body
     * @return mixed
     */
    private function writeInto(string $definition, $entity, array $body)
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::EDIT);

        $resource = $transformer->fromArray($definition, $body, $context);

        return $transformer->toEntity($resource, new ResolverEntityFactory(), $context, $entity);
    }
}

class ResolverProfile
{
    public function __construct(private $nickname = null)
    {
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname($nickname): void
    {
        $this->nickname = $nickname;
    }
}

class ResolverEntity
{
    public $slug = 'a-slug';

    private ?ResolverProfile $profile = null;

    public function getProfile(): ?ResolverProfile
    {
        return $this->profile;
    }

    public function setProfile(ResolverProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function isPublished(): bool
    {
        return true;
    }

    public function getShout($suffix = null): string
    {
        return 'shout' . $suffix;
    }
}

/**
 * An entity that keeps its values in a bag, the way an ORM model does.
 */
class ResolverAttributeBag
{
    private array $attributes = [];

    public function hasAttribute($name): bool
    {
        return $name === 'title';
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
}

class ResolverReadDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->field('profile.nickname')
                ->display('nickname')
                ->visible()

            ->field('published')
                ->visible()

            ->field('slug')
                ->visible()
        ;
    }
}

class ResolverParameterDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->field('shout:{context.suffix}')
                ->display('shout')
                ->visible()
        ;
    }
}

class ResolverOptionalParameterDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->field('shout:{context.suffix?}')
                ->display('shout')
                ->visible()
        ;
    }
}

class ResolverWriteDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->field('profile.nickname')
                ->display('nickname')
                ->writeable()
                ->visible()

            ->field('unsettable')
                ->writeable()
                ->visible()
        ;
    }
}

class ResolverAttributeBagDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverAttributeBag::class);

        $this
            ->field('title')
                ->writeable()
                ->visible()
        ;
    }
}

class ResolverNestedInputDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->field('nickname')
                ->display('meta.nickname')
                ->writeable()
                ->visible()
        ;
    }
}

class ResolverEditDetectionChildDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverProfile::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->writeable()
                ->visible()

            // Writeable on EDIT only, so its presence in the parsed child
            // resource reveals which action the child context was given.
            ->field('editOnly')
                ->writeable(false, true)
                ->visible()
        ;
    }
}

class ResolverEditDetectionDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ResolverEntity::class);

        $this
            ->relationship('items', ResolverEditDetectionChildDefinition::class)
                ->many()
                ->writeable()
                ->visible()

            ->relationship('child', ResolverEditDetectionChildDefinition::class)
                ->one()
                ->writeable()
                ->visible()
        ;
    }
}

class ResolverEntityFactory implements EntityFactory
{
    public function createEntity($entityClassName, ContextContract $context)
    {
        return new $entityClassName();
    }

    public function resolveLinkedEntity($parent, string $entityClassName, Identifier $identifier, ContextContract $context)
    {
        return null;
    }

    public function resolveFromIdentifier(string $entityClassName, Identifier $identifier, ContextContract $context)
    {
        return null;
    }
}
