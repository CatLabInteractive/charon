<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\InvalidContextAction;
use CatLab\Charon\Exceptions\InvalidEntityException;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\DynamicContext;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\ResourceDefinition;
use Tests\Models\MockEntityModel;
use Tests\Models\MockResourceDefinition;

/**
 * Class ResourceTransformerTest
 */
final class ResourceTransformerTest extends BaseTest
{
    /**
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     * @throws \CatLab\Charon\Exceptions\InvalidEntityException
     * @throws \CatLab\Charon\Exceptions\InvalidPropertyException
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     * @throws \CatLab\Charon\Exceptions\IterableExpected
     */
    public function testResourceTransformer(): void
    {
        MockEntityModel::clearNextId();
        $model = new MockEntityModel();
        $model->addChildren();

        $definition = MockResourceDefinition::class;

        $transformer = $this->getResourceTransformer();

        $context = new \CatLab\Charon\Models\Context(
            \CatLab\Charon\Enums\Action::VIEW,
            [
                'childNumber' => 2
            ]
        );

        $resource = $transformer->toResource($definition, $model, $context);

        $this->assertEquals(
            [
                'name' => 1,
                'firstChild' => [
                    'name' => 2,
                ],
                'nthChild' => [
                    'name' => 4
                ]
            ],
            $resource->toArray()
        );
    }

    /**
     * toResources() is the list endpoint of every API built on charon.
     */
    public function testToResourcesTransformsEveryEntityInOrder(): void
    {
        $resources = $this->getResourceTransformer()->toResources(
            TransformerReadDefinition::class,
            [ new TransformerEntity(1, 'one'), new TransformerEntity(2, 'two') ],
            new Context(Action::INDEX)
        );

        $this->assertSame(
            [
                [ 'id' => 1, 'name' => 'one' ],
                [ 'id' => 2, 'name' => 'two' ],
            ],
            $resources->toArray()[\CatLab\Charon\ResourceTransformer::RELATIONSHIP_ITEMS]
        );
    }

    /**
     * A query builder that was never executed, a null relationship, a single
     * entity passed where a list was meant: all of them arrive here as
     * something that cannot be walked, and the message has to say so rather
     * than producing an empty list.
     */
    public function testToResourcesRejectsANonIterable(): void
    {
        $this->expectException(InvalidEntityException::class);

        $this->getResourceTransformer()->toResources(
            TransformerReadDefinition::class,
            new TransformerEntity(1, 'one'),
            new Context(Action::INDEX)
        );
    }

    /**
     * Transforming an entity of the wrong class would read fields off it more
     * or less at random, so the type is checked up front.
     */
    public function testToResourceRejectsAnEntityOfTheWrongType(): void
    {
        $this->expectException(InvalidEntityException::class);

        $this->getResourceTransformer()->toResource(
            TransformerReadDefinition::class,
            new \stdClass(),
            new Context(Action::VIEW)
        );
    }

    /**
     * Read and write contexts are not interchangeable: a definition's fields
     * declare visibility per action, so reading with a write action (or the
     * reverse) would apply the wrong set of fields entirely.
     */
    public function testReadingRequiresAReadContext(): void
    {
        $this->expectException(InvalidContextAction::class);

        $this->getResourceTransformer()->toResource(
            TransformerReadDefinition::class,
            new TransformerEntity(1, 'one'),
            new Context(Action::CREATE)
        );
    }

    public function testWritingRequiresAWriteContext(): void
    {
        $this->expectException(InvalidContextAction::class);

        $this->getResourceTransformer()->fromArray(
            TransformerReadDefinition::class,
            [ 'name' => 'one' ],
            new Context(Action::VIEW)
        );
    }

    /**
     * entitiesFromIdentifiers() backs the link/unlink endpoints: a body of bare
     * identifiers becomes a list of entities, resolved through the factory.
     * Identifiers that resolve to nothing are dropped rather than becoming
     * nulls in the list.
     */
    public function testEntitiesFromIdentifiersResolvesAListOfIdentifiers(): void
    {
        $one = new TransformerEntity(1, 'one');
        $two = new TransformerEntity(2, 'two');

        $entities = $this->getResourceTransformer()->entitiesFromIdentifiers(
            TransformerReadDefinition::class,
            [ \CatLab\Charon\ResourceTransformer::RELATIONSHIP_ITEMS => [ [ 'id' => 1 ], [ 'id' => 404 ], [ 'id' => 2 ] ] ],
            new TransformerEntityFactory([ 1 => $one, 2 => $two ]),
            new Context(Action::EDIT)
        );

        $this->assertSame([ $one, $two ], $entities);
    }

    public function testEntitiesFromIdentifiersAcceptsASingleIdentifier(): void
    {
        $one = new TransformerEntity(1, 'one');

        $entities = $this->getResourceTransformer()->entitiesFromIdentifiers(
            TransformerReadDefinition::class,
            [ 'id' => 1 ],
            new TransformerEntityFactory([ 1 => $one ]),
            new Context(Action::EDIT)
        );

        $this->assertSame([ $one ], $entities);
    }

    /**
     * An expandable "many" relationship that resolves to null (an unloaded or
     * simply empty collection) must render as an empty collection - not be left
     * out of the resource, and not blow up on the way.
     */
    public function testANullManyRelationshipExpandsToAnEmptyCollection(): void
    {
        $context = new Context(Action::VIEW);
        $context->expandField('children');

        $resource = $this->getResourceTransformer()->toResource(
            TransformerRelationshipDefinition::class,
            new TransformerEntity(1, 'one'),
            $context
        );

        $this->assertSame(
            [ \CatLab\Charon\ResourceTransformer::RELATIONSHIP_ITEMS => [] ],
            $resource->toArray()['children']
        );
    }

    /**
     * An entity implementing DynamicContext may narrow the context for itself.
     * toResource() forks the context before handing it over precisely so that
     * this cannot leak: the next entity in the same collection must still be
     * transformed with the original context.
     */
    public function testDynamicContextIsForkedPerEntity(): void
    {
        $resources = $this->getResourceTransformer()->toResources(
            TransformerReadDefinition::class,
            [ new TransformerSecretiveEntity(1, 'one'), new TransformerEntity(2, 'two') ],
            new Context(Action::INDEX)
        );

        $this->assertSame(
            [
                [ 'id' => 1 ],
                [ 'id' => 2, 'name' => 'two' ],
            ],
            $resources->toArray()[\CatLab\Charon\ResourceTransformer::RELATIONSHIP_ITEMS],
            'A DynamicContext entity must not narrow the context of its siblings.'
        );
    }
}

class TransformerEntity
{
    public function __construct(private $id = null, private $name = null)
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * The relationship is never loaded, which reaches the transformer as null.
     * @return TransformerEntity[]|null
     */
    public function getChildren(): ?array
    {
        return null;
    }
}

/**
 * An entity that narrows the context to its identifier only - the shape of a
 * per-record permission check.
 */
class TransformerSecretiveEntity extends TransformerEntity implements DynamicContext
{
    public function transformContext(ContextContract $context, $entity)
    {
        $context->showFields([ 'id' ]);
        return $context;
    }
}

class TransformerReadDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(TransformerEntity::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->writeable()
                ->visible(true, true)
        ;
    }
}

class TransformerRelationshipDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(TransformerEntity::class);

        $this
            ->relationship('children', TransformerReadDefinition::class)
                ->many()
                ->expandable()
                ->visible(true, true)
        ;
    }
}

class TransformerEntityFactory implements EntityFactory
{
    /**
     * @param array $entities id => entity
     */
    public function __construct(private readonly array $entities = [])
    {
    }

    public function createEntity($entityClassName, ContextContract $context)
    {
        return new $entityClassName();
    }

    public function resolveLinkedEntity($parent, string $entityClassName, Identifier $identifier, ContextContract $context)
    {
        return $this->resolveFromIdentifier($entityClassName, $identifier, $context);
    }

    public function resolveFromIdentifier(string $entityClassName, Identifier $identifier, ContextContract $context)
    {
        foreach ($identifier->getIdentifiers()->getValues() as $value) {
            $id = $value->getValue();
            if ($id !== null && isset($this->entities[$id])) {
                return $this->entities[$id];
            }
        }

        return null;
    }
}
