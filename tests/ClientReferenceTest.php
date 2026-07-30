<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\ClientReferenceMap;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\ResourceTransformer as AbstractResourceTransformer;
use Tests\Petstore\Definitions\PetDefinition;

/**
 * Class ClientReferenceTest
 *
 * Covers the client-references ($ref) feature: a payload resource carrying
 * "$ref": "tmp-1" registers its entity under 'tmp-1' at creation, and a
 * linkable child consisting only of {"$ref": "tmp-1"} resolves through the
 * ClientReferenceMap instead of the database.
 */
final class ClientReferenceTest extends BaseTest
{
    /**
     * The $ref key must be captured on the resource via RESTResource::getClientRef(),
     * and must not leak into the resource's regular properties.
     */
    public function testClientRefIsCapturedOnInputResource(): void
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(
            PetDefinition::class,
            [
                '$ref' => 'tmp-1',
                'name' => 'Foobar',
            ],
            $context
        );

        $this->assertSame('tmp-1', $resource->getClientRef());

        // '$ref' must not appear as a regular property/field value.
        $this->assertNull($resource->getProperties()->getFromName('$ref'));
        $this->assertArrayNotHasKey('$ref', $resource->toArray());
    }

    /**
     * A resource without a '$ref' key simply has no client reference.
     */
    public function testNoClientRefWhenNotProvided(): void
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(
            PetDefinition::class,
            [
                'name' => 'Foobar',
            ],
            $context
        );

        $this->assertNull($resource->getClientRef());
    }

    /**
     * A nested (child) resource can also carry its own '$ref', captured through
     * the exact same fromArray() path used recursively for relationships.
     */
    public function testClientRefIsCapturedOnNestedChildResource(): void
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(
            PetDefinition::class,
            [
                'name' => 'Foobar',
                'photos' => [
                    'items' => [
                        [
                            '$ref' => 'photo-tmp-1',
                            'url' => 'photo1.jpg',
                        ],
                    ],
                ],
            ],
            $context
        );

        $photos = $resource->getProperties()->getFromName('photos')->getChildren();
        $this->assertCount(1, $photos);
        $this->assertSame('photo-tmp-1', $photos[0]->getClientRef());
    }

    public function testMapRegistersAndResolves(): void
    {
        $map = new ClientReferenceMap();
        $entity = new \stdClass();
        $map->register('tmp-1', $entity);
        $this->assertSame($entity, $map->resolve('tmp-1'));
        $this->assertNull($map->resolve('tmp-2'));
    }

    public function testDeferredResolutionRunsWhenRegistered(): void
    {
        $map = new ClientReferenceMap();
        $applied = [];
        $map->defer('tmp-1', function ($e) use (&$applied) {
            $applied[] = $e;
        });
        $this->assertSame(['tmp-1'], array_keys($map->getUnresolvedDeferred()));
        $entity = new \stdClass();
        $map->register('tmp-1', $entity);
        $unresolved = $map->runDeferred();
        $this->assertSame([], $unresolved);
        $this->assertSame([$entity], $applied);
    }

    public function testUnresolvedDeferredRefsAreReported(): void
    {
        $map = new ClientReferenceMap();
        $map->defer('tmp-x', function () {
        });
        $this->assertSame(['tmp-x'], $map->runDeferred());
    }

    public function testGetMappingAndHasAny(): void
    {
        $map = new ClientReferenceMap();
        $this->assertFalse($map->hasAny());
        $this->assertSame([], $map->getMapping());

        $entity = new \stdClass();
        $map->register('tmp-1', $entity);

        $this->assertTrue($map->hasAny());
        $this->assertSame(['tmp-1' => $entity], $map->getMapping());
    }

    /**
     * getPendingLinks() drain semantics: pending links are recorded eagerly as
     * [parent, field, ref] tuples (charon cannot apply them itself -- it lacks
     * a PropertySetter context at defer time), and the consumer resolves the
     * ref against the map later, once the whole payload has been processed.
     */
    public function testPendingLinksAreRecordedAndResolvedAtDrainTime(): void
    {
        $map = new ClientReferenceMap();

        $parent = new \stdClass();
        $petDefinition = new PetDefinition();
        /** @var RelationshipField $field */
        $field = $petDefinition->getFields()->getFromName('tags');

        $map->addPendingLink($parent, $field, 'tmp-1');

        $this->assertCount(1, $map->getPendingLinks());
        $link = $map->getPendingLinks()[0];
        $this->assertSame($parent, $link['parent']);
        $this->assertSame($field, $link['field']);
        $this->assertSame('tmp-1', $link['ref']);

        // Not resolvable yet.
        $this->assertNull($map->resolve($link['ref']));

        // Once the referenced entity is registered, the consumer can resolve it.
        $entity = new \stdClass();
        $map->register('tmp-1', $entity);
        $this->assertSame($entity, $map->resolve($link['ref']));
    }

    public function testContextLazilyReturnsSameClientReferenceMapInstance(): void
    {
        $context = new Context(Action::CREATE);
        $map = $context->getClientReferenceMap();

        $this->assertInstanceOf(ClientReferenceMap::class, $map);
        $this->assertSame($map, $context->getClientReferenceMap());
    }

    /**
     * A child context (as created for nested relationship fields) must share the
     * same ClientReferenceMap as its parent, since client references must resolve
     * across the whole payload, not per relationship subtree.
     */
    public function testChildContextSharesClientReferenceMapWithParent(): void
    {
        $context = new Context(Action::CREATE);
        $map = $context->getClientReferenceMap();

        $petDefinition = new PetDefinition();
        /** @var RelationshipField $field */
        $field = $petDefinition->getFields()->getFromName('photos');

        $childContext = $context->getChildContext($field, Action::CREATE);

        $this->assertSame($map, $childContext->getClientReferenceMap());
    }

    /**
     * Full round-trip integration test: a parent resource has two sibling
     * relationships -- one that creates new children (registering a $ref),
     * and one that links to an existing entity purely by $ref. The linking
     * field is declared (and therefore processed) BEFORE the creating field,
     * so the reference cannot resolve immediately: it must be recorded as a
     * pending link and only becomes resolvable once the whole resource has
     * been walked.
     */
    public function testChildResourceToEntityWiresPendingLinksAndRegistersNewChildren(): void
    {
        $transformer = $this->getResourceTransformer();
        $factory = new ClientRefTestEntityFactory();
        $context = new Context(Action::CREATE);

        $resource = $transformer->fromArray(
            ClientRefParentDefinition::class,
            [
                'name' => 'parent-1',
                'link' => [
                    '$ref' => 'tmp-1',
                ],
                'items' => [
                    'items' => [
                        [
                            '$ref' => 'tmp-1',
                            'name' => 'child-1',
                        ],
                    ],
                ],
            ],
            $context
        );

        /** @var ClientRefParentEntity $entity */
        $entity = $transformer->toEntity($resource, $factory, $context);

        $this->assertInstanceOf(ClientRefParentEntity::class, $entity);
        $this->assertSame('parent-1', $entity->getName());

        // The new child was created and registered under 'tmp-1'.
        $this->assertCount(1, $entity->getItems());
        $childEntity = $entity->getItems()[0];
        $this->assertInstanceOf(ClientRefChildEntity::class, $childEntity);
        $this->assertSame('child-1', $childEntity->getName());

        $map = $context->getClientReferenceMap();
        $this->assertSame($childEntity, $map->resolve('tmp-1'));

        // The linkable field could not resolve immediately (it is processed
        // before the child was created), so charon itself must NOT have set
        // it, and instead must have recorded a pending link.
        $this->assertNull($entity->getLink());

        $pendingLinks = $map->getPendingLinks();
        $this->assertCount(1, $pendingLinks);
        $this->assertSame($entity, $pendingLinks[0]['parent']);
        $this->assertSame('tmp-1', $pendingLinks[0]['ref']);
        $this->assertInstanceOf(RelationshipField::class, $pendingLinks[0]['field']);
        $this->assertSame('link', $pendingLinks[0]['field']->getName());

        // The consumer (charon-laravel) would now drain pending links like this:
        $this->assertSame($childEntity, $map->resolve($pendingLinks[0]['ref']));
    }

    /**
     * If a linkable-only $ref child resolves immediately (because the
     * referenced entity was already registered earlier in the payload), it
     * must be linked directly -- no pending link should be recorded.
     */
    public function testChildResourceToEntityResolvesImmediatelyWhenAlreadyRegistered(): void
    {
        $transformer = $this->getResourceTransformer();
        $factory = new ClientRefTestEntityFactory();
        $context = new Context(Action::CREATE);

        // Uses ClientRefParentDefinitionItemsFirst, which declares 'items' before
        // 'link' -- so by the time 'link' is processed, 'tmp-1' is already
        // registered and can resolve immediately.
        $resource = $transformer->fromArray(
            ClientRefParentDefinitionItemsFirst::class,
            [
                'name' => 'parent-1',
                'items' => [
                    'items' => [
                        [
                            '$ref' => 'tmp-1',
                            'name' => 'child-1',
                        ],
                    ],
                ],
                'link' => [
                    '$ref' => 'tmp-1',
                ],
            ],
            $context
        );

        /** @var ClientRefParentEntity $entity */
        $entity = $transformer->toEntity($resource, $factory, $context);

        $childEntity = $entity->getItems()[0];

        $this->assertSame($childEntity, $entity->getLink());

        $map = $context->getClientReferenceMap();
        $this->assertSame([], $map->getPendingLinks());
    }
}

/**
 * Minimal test fixtures for the RelationshipValue::childResourceToEntity()
 * integration test. Kept in this file (not autoloaded) since they only exist
 * to exercise the client-reference wiring end-to-end.
 */
class ClientRefChildEntity
{
    private $id;
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}

class ClientRefParentEntity
{
    private $name;

    /** @var ClientRefChildEntity[] */
    private array $items = [];

    private $link;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    /** @return ClientRefChildEntity[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    public function editItems(array $items): void
    {
        // no-op: not exercised in these tests
    }

    public function removeItems($items): void
    {
        // no-op: not exercised in these tests
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link): void
    {
        $this->link = $link;
    }
}

class ClientRefChildDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClientRefChildEntity::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->writeable()
                ->visible()
        ;
    }
}

class ClientRefParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClientRefParentEntity::class);

        $this
            ->field('name')
                ->writeable()
                ->visible()

            // Declared (and therefore processed) BEFORE 'items' on purpose: this
            // is what forces the forward-reference / pending-link code path.
            ->relationship('link', ClientRefChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()

            ->relationship('items', ClientRefChildDefinition::class)
                ->many()
                ->writeable()
                ->visible()
        ;
    }
}

/**
 * Same shape as ClientRefParentDefinition, but declares 'items' before 'link'
 * so that the linkable $ref resolves immediately instead of being deferred.
 */
class ClientRefParentDefinitionItemsFirst extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClientRefParentEntity::class);

        $this
            ->field('name')
                ->writeable()
                ->visible()

            ->relationship('items', ClientRefChildDefinition::class)
                ->many()
                ->writeable()
                ->visible()

            ->relationship('link', ClientRefChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()
        ;
    }
}

class ClientRefTestEntityFactory implements EntityFactory
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
