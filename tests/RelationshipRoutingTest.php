<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\ChildNotEditableException;
use CatLab\Charon\Exceptions\EntityNotFoundException;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * RelationshipValue::childResourceToEntity() decides, per child in a write
 * payload, whether that child is linked, created, edited or dropped. It is the
 * single most consequential branch in charon - the difference between
 * writeable() and linkable() on a relationship is decided here, not at
 * definition time - and it is where the most recent production bugs lived.
 *
 * These tests drive the decision through the real transformer, a real
 * PropertySetter and real entities, and assert on what happened to the parent
 * entity (which methods were called with which children), because that is the
 * only thing consumers actually observe.
 */
final class RelationshipRoutingTest extends BaseTest
{
    /**
     * The trap: writeable() sets createActions, linkable() sets linkActions,
     * and linkable() internally calls writeable() on the field itself - so both
     * make the relationship writeable and the two are easy to confuse. When a
     * payload carries a child that is ALREADY attached to the parent, a
     * writeable() relationship routes it into editChildren(), which needs an
     * edit<Name>() method on the entity. Without one it throws - at request
     * time, on a definition that looked fine.
     */
    public function testWriteableRelationshipRoutesAnAlreadyAttachedChildIntoEditChildren(): void
    {
        $parent = new RoutingParent();
        $parent->addItems([ new RoutingChild(1, 'original') ]);

        $this->expectException(ChildNotEditableException::class);

        $this->write(
            RoutingWriteableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'name' => 'renamed' ] ] ] ]
        );
    }

    /**
     * The same payload against an entity that CAN edit its children: the child
     * must reach editItems() (not addItems()), and must be the entity that was
     * already attached - charon updates it in place rather than making a second
     * one.
     */
    public function testWriteableRelationshipEditsTheExistingChildInPlace(): void
    {
        $existing = new RoutingChild(1, 'original');
        $parent = new RoutingEditableParent();
        $parent->addItems([ $existing ]);

        $this->write(
            RoutingEditableWriteableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'name' => 'renamed' ] ] ] ]
        );

        $this->assertSame([ 'add', 'edit' ], $parent->calls);
        $this->assertSame([ $existing ], $parent->edited);
        $this->assertSame('renamed', $existing->getName());
        $this->assertSame([ $existing ], $parent->getItems());
    }

    /**
     * linkable() (and NOT writeable()) on the same relationship: an already
     * attached child is recognised, kept, and left completely alone - no
     * editChildren() call (so no edit<Name>() method needed), and crucially no
     * removal either, because its identifier was recorded in identifiersToKeep.
     */
    public function testLinkableRelationshipLeavesAnAlreadyAttachedChildUntouched(): void
    {
        $existing = new RoutingChild(1, 'original');
        $parent = new RoutingParent();
        $parent->addItems([ $existing ]);
        $parent->calls = [];

        $this->write(
            RoutingLinkableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 1, 'name' => 'renamed' ] ] ] ]
        );

        $this->assertSame([], $parent->calls, 'A linkable relationship must not add, edit or remove an already-linked child.');
        $this->assertSame([ $existing ], $parent->getItems());
        $this->assertSame('original', $existing->getName(), 'linkable() links, it does not write child attributes.');
    }

    /**
     * A linkable relationship pointed at an existing entity that is not
     * currently attached: the entity comes from the EntityFactory (not from a
     * newly constructed object), gets added, and - because its identifier is in
     * identifiersToKeep while the previously attached child's is not - the old
     * child is removed by removeAllChildrenExcept().
     */
    public function testLinkableRelationshipSwapsInAnExistingEntityAndRemovesTheRest(): void
    {
        $old = new RoutingChild(1, 'old');
        $other = new RoutingChild(2, 'other');

        $parent = new RoutingParent();
        $parent->addItems([ $old ]);
        $parent->calls = [];

        $this->write(
            RoutingLinkableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 2 ] ] ] ],
            [ 2 => $other ]
        );

        $this->assertSame([ $other ], $parent->getItems());
        $this->assertSame([ 'add', 'remove' ], $parent->calls);
        $this->assertSame([ $old ], $parent->removed, 'The child that was not in the payload must be removed.');
    }

    /**
     * removeAllChildrenExcept() runs even when the payload's list is empty, so
     * an explicit empty items array clears the relationship.
     */
    public function testAnEmptyChildListRemovesEveryExistingChild(): void
    {
        $one = new RoutingChild(1, 'one');
        $two = new RoutingChild(2, 'two');

        $parent = new RoutingParent();
        $parent->addItems([ $one, $two ]);
        $parent->calls = [];

        $this->write(
            RoutingLinkableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [] ] ]
        );

        $this->assertSame([], $parent->getItems());
        $this->assertSame([ $one, $two ], $parent->removed);
    }

    /**
     * Omitting the relationship from the payload entirely is not the same as
     * sending an empty list: nothing about it may be touched.
     */
    public function testOmittingTheRelationshipLeavesItAlone(): void
    {
        $one = new RoutingChild(1, 'one');

        $parent = new RoutingParent();
        $parent->addItems([ $one ]);
        $parent->calls = [];

        $this->write(RoutingLinkableManyDefinition::class, $parent, [ 'name' => 'irrelevant' ]);

        $this->assertSame([ $one ], $parent->getItems());
        $this->assertSame([], $parent->calls);
    }

    /**
     * A child that is new to this parent but exists in the database is only
     * ever *linked* by a relationship that declares linkable(). A
     * writeable()-only relationship cannot link: it runs the child through
     * toEntity() with no existing entity, which asks the factory for a brand
     * new one. The id in the payload does not save it.
     */
    public function testWriteableOnlyRelationshipCreatesRatherThanLinksAnExistingId(): void
    {
        $other = new RoutingChild(2, 'other');

        $parent = new RoutingParent();

        $this->write(
            RoutingWriteableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 2, 'name' => 'other' ] ] ] ],
            [ 2 => $other ]
        );

        $this->assertCount(1, $parent->getItems());
        $this->assertNotSame($other, $parent->getItems()[0], 'writeable() must not link the existing entity - only linkable() does that.');
    }

    /**
     * A linkable-only relationship pointed at an id the factory cannot resolve
     * has nowhere left to go: it may not quietly create the missing record.
     */
    public function testLinkableRelationshipRejectsAnIdentifierThatCannotBeResolved(): void
    {
        $parent = new RoutingParent();

        $this->expectException(EntityNotFoundException::class);

        $this->write(
            RoutingLinkableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'id' => 404 ] ] ] ]
        );
    }

    /**
     * Same rule from the other direction: a linkable-only relationship may not
     * create an inline child that carries no identifier at all.
     */
    public function testLinkableRelationshipRejectsAnInlineNewChild(): void
    {
        $parent = new RoutingParent();

        $this->expectException(EntityNotFoundException::class);

        $this->write(
            RoutingLinkableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'name' => 'brand new' ] ] ] ]
        );
    }

    /**
     * A writeable relationship does create inline children, and they reach the
     * parent through addChildren() (not editChildren()) since there is no
     * existing entity to edit.
     */
    public function testWriteableRelationshipCreatesInlineChildren(): void
    {
        $parent = new RoutingParent();

        $this->write(
            RoutingWriteableManyDefinition::class,
            $parent,
            [ 'items' => [ 'items' => [ [ 'name' => 'brand new' ] ] ] ]
        );

        $this->assertSame([ 'add' ], $parent->calls);
        $this->assertCount(1, $parent->getItems());
        $this->assertSame('brand new', $parent->getItems()[0]->getName());
    }

    /**
     * canCreateNewChildren() / canLinkExistingEntities() are per-action, and
     * the routing has to honour that: this relationship may create children in
     * either context but may only link an existing one on EDIT. Same
     * definition, same payload, two different outcomes.
     */
    public function testCreateAndLinkPermissionsAreEvaluatedPerAction(): void
    {
        $existing = new RoutingChild(7, 'existing');

        $created = new RoutingParent();
        $this->write(
            RoutingCreateOnCreateLinkOnEditDefinition::class,
            $created,
            [ 'items' => [ 'items' => [ [ 'id' => 7, 'name' => 'existing' ] ] ] ],
            [ 7 => $existing ],
            Action::CREATE
        );

        $this->assertCount(1, $created->getItems());
        $this->assertNotSame($existing, $created->getItems()[0], 'On CREATE this relationship creates.');

        $edited = new RoutingParent();
        $this->write(
            RoutingCreateOnCreateLinkOnEditDefinition::class,
            $edited,
            [ 'items' => [ 'items' => [ [ 'id' => 7, 'name' => 'existing' ] ] ] ],
            [ 7 => $existing ],
            Action::EDIT
        );

        $this->assertSame([ $existing ], $edited->getItems(), 'On EDIT this relationship links.');
    }

    /**
     * Cardinality ONE goes through ChildValue instead of ChildrenValue, which
     * routes through setChild()/clearChild() rather than add/remove. A linkable
     * one-relationship pointed at an existing entity must set that very entity.
     */
    public function testLinkableOneRelationshipSetsTheResolvedEntity(): void
    {
        $other = new RoutingChild(2, 'other');
        $parent = new RoutingParent();

        $this->write(
            RoutingLinkableOneDefinition::class,
            $parent,
            [ 'child' => [ 'id' => 2 ] ],
            [ 2 => $other ]
        );

        $this->assertSame($other, $parent->getChild());
    }

    /**
     * The one-relationship equivalent of "keep what was sent": the child that
     * is already set and is named in the payload must survive
     * removeAllChildrenExcept(), which for ChildValue means clearChild() must
     * NOT be called.
     */
    public function testLinkableOneRelationshipKeepsTheChildItAlreadyPointsAt(): void
    {
        $existing = new RoutingChild(1, 'existing');
        $parent = new RoutingParent();
        $parent->setChild($existing);

        $this->write(
            RoutingLinkableOneDefinition::class,
            $parent,
            [ 'child' => [ 'id' => 1 ] ],
            [ 1 => $existing ]
        );

        $this->assertSame($existing, $parent->getChild());
    }

    /**
     * Explicit null on a one-relationship clears it: nothing lands in
     * identifiersToKeep, so ChildValue::removeAllChildrenExcept() clears the
     * child.
     *
     * Note this only holds when the PARENT resource definition declares an
     * identifier - see PropertySetter::clearChild(), which checks the existing
     * child against $field->getResourceDefinition() (the parent's) identifiers.
     */
    public function testNullOnAOneRelationshipClearsIt(): void
    {
        $existing = new RoutingChild(1, 'existing');
        $parent = new RoutingParent();
        $parent->setId(99);
        $parent->setChild($existing);

        $this->write(
            RoutingIdentifiedLinkableOneDefinition::class,
            $parent,
            [ 'child' => null ]
        );

        $this->assertNull($parent->getChild());
    }

    /**
     * @param string $definition
     * @param mixed $entity
     * @param array $body
     * @param array $linkable id => entity, resolvable by the factory
     * @param string $action
     * @return mixed
     */
    private function write(
        string $definition,
        $entity,
        array $body,
        array $linkable = [],
        string $action = Action::EDIT
    ) {
        $transformer = $this->getResourceTransformer();
        $context = new Context($action);

        $resource = $transformer->fromArray($definition, $body, $context);

        return $transformer->toEntity($resource, new RoutingEntityFactory($linkable), $context, $entity);
    }
}

/**
 * Fixtures. Kept in this file (like ClientReferenceTest's) since they only
 * exist to drive the routing decision.
 */
class RoutingChild
{
    public function __construct(private $id = null, private $name = null)
    {
    }

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

/**
 * A parent that can add and remove children, but cannot edit them - the common
 * case, and the one that turns a writeable()-instead-of-linkable() mistake into
 * a ChildNotEditableException.
 */
class RoutingParent
{
    /** @var string[] */
    public array $calls = [];

    /** @var RoutingChild[] */
    public array $removed = [];

    private $id;

    /** @var RoutingChild[] */
    private array $items = [];

    private $child;

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

    /** @return RoutingChild[] */
    public function getItems(): array
    {
        return array_values($this->items);
    }

    public function addItems(array $items): void
    {
        $this->calls[] = 'add';
        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    public function removeItems($items): void
    {
        $this->calls[] = 'remove';
        foreach ($items as $item) {
            $this->removed[] = $item;
            $index = array_search($item, $this->items, true);
            if ($index !== false) {
                unset($this->items[$index]);
            }
        }
    }

    public function getChild()
    {
        return $this->child;
    }

    public function setChild($child): void
    {
        $this->calls[] = $child === null ? 'clearChild' : 'setChild';
        $this->child = $child;
    }
}

class RoutingEditableParent extends RoutingParent
{
    /** @var RoutingChild[] */
    public array $edited = [];

    public function editItems(array $items): void
    {
        $this->calls[] = 'edit';
        foreach ($items as $item) {
            $this->edited[] = $item;
        }
    }
}

class RoutingChildDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingChild::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->writeable()
                ->visible()
        ;
    }
}

class RoutingWriteableManyDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingParent::class);

        $this
            ->field('name')
                ->writeable()
                ->visible()

            ->relationship('items', RoutingChildDefinition::class)
                ->many()
                ->writeable()
                ->visible()
        ;
    }
}

class RoutingEditableWriteableManyDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingEditableParent::class);

        $this
            ->relationship('items', RoutingChildDefinition::class)
                ->many()
                ->writeable()
                ->visible()
        ;
    }
}

class RoutingLinkableManyDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingParent::class);

        $this
            ->field('name')
                ->writeable()
                ->visible()

            ->relationship('items', RoutingChildDefinition::class)
                ->many()
                ->linkable()
                ->visible()
        ;
    }
}

/**
 * May create new children in either context, but may only link an existing one
 * on EDIT.
 *
 * Note the declaration order: linkable() and writeable() both call
 * Field::writeable() on the field itself, each overwriting the other's
 * per-action writeability, so the wider of the two has to come last.
 */
class RoutingCreateOnCreateLinkOnEditDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingParent::class);

        $this
            ->relationship('items', RoutingChildDefinition::class)
                ->many()
                ->linkable(false, true)
                ->writeable(true, true)
                ->visible()
        ;
    }
}

class RoutingLinkableOneDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingParent::class);

        $this
            ->relationship('child', RoutingChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()
        ;
    }
}

/**
 * Same as RoutingLinkableOneDefinition, but the parent resource itself declares
 * an identifier.
 */
class RoutingIdentifiedLinkableOneDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(RoutingParent::class);

        $this
            ->identifier('id')
                ->int()

            ->relationship('child', RoutingChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()
        ;
    }
}

class RoutingEntityFactory implements EntityFactory
{
    /**
     * @param array $linkable id => entity
     */
    public function __construct(private readonly array $linkable = [])
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
        $values = $identifier->getIdentifiers()->getValues();
        foreach ($values as $value) {
            $id = $value->getValue();
            if ($id !== null && isset($this->linkable[$id])) {
                return $this->linkable[$id];
            }
        }

        return null;
    }
}
