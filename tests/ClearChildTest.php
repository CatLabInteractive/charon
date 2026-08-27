<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Models\Context;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Sending null for a cardinality-one relationship means "unlink whatever is
 * there". That request travels through ChildValue::removeAllChildrenExcept()
 * (nothing to keep) into PropertySetter::clearChild().
 *
 * clearChild() guards that write: an existing child that was never persisted
 * is a child the parent itself just created in this same request, and clearing
 * it would throw away work rather than unlink a link. The identity it checks
 * has to be read from the CHILD's resource definition - the entity it is
 * looking at is a child.
 */
final class ClearChildTest extends BaseTest
{
    /**
     * The parent declares no identifier of its own, which is perfectly legal -
     * plenty of resources are only ever reached through their parent.
     *
     * Reading the identifiers off the parent definition makes the check ask
     * "does this child have the parent's identifier fields?", and with an empty
     * identifier list the answer is always no: the null is silently dropped and
     * the old child stays linked while the write reports success.
     */
    public function testNullClearsTheChildWhenTheParentDeclaresNoIdentifier(): void
    {
        $existing = new ClearChildChild(1, 'existing');
        $parent = new ClearChildParent();
        $parent->setChild($existing);

        $this->write(ClearChildParentDefinition::class, $parent, [ 'child' => null ]);

        $this->assertNull($parent->getChild(), 'Sending null for a one-relationship must unlink the child.');
    }

    /**
     * The same, with an identifier on the parent. This case already worked; it
     * is here so the fix cannot quietly trade one broken definition shape for
     * another.
     */
    public function testNullClearsTheChildWhenTheParentDeclaresAnIdentifier(): void
    {
        $existing = new ClearChildChild(1, 'existing');
        $parent = new ClearChildParent();
        $parent->setId(99);
        $parent->setChild($existing);

        $this->write(ClearChildIdentifiedParentDefinition::class, $parent, [ 'child' => null ]);

        $this->assertNull($parent->getChild());
    }

    /**
     * What the guard is for: the child currently on the entity carries no
     * identifier, so it does not exist yet as far as the outside world is
     * concerned. Clearing it would drop a brand new object on the floor, so
     * clearChild() must leave it alone.
     */
    public function testNullDoesNotClearAChildThatWasNeverPersisted(): void
    {
        $new = new ClearChildChild(null, 'not saved yet');
        $parent = new ClearChildParent();
        $parent->setChild($new);

        $this->write(ClearChildParentDefinition::class, $parent, [ 'child' => null ]);

        $this->assertSame($new, $parent->getChild(), 'A child without an identifier is new and must not be cleared.');
        $this->assertNotContains('clearChild', $parent->calls);
    }

    /**
     * @param string $definition
     * @param mixed $entity
     * @param array $body
     * @return mixed
     */
    private function write(string $definition, $entity, array $body)
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::EDIT);

        $resource = $transformer->fromArray($definition, $body, $context);

        return $transformer->toEntity($resource, new ClearChildEntityFactory(), $context, $entity);
    }
}

class ClearChildChild
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

class ClearChildParent
{
    /** @var string[] */
    public array $calls = [];

    private $id;

    private $child;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
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

class ClearChildChildDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClearChildChild::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->writeable()
                ->visible()
        ;
    }
}

class ClearChildParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClearChildParent::class);

        $this
            ->relationship('child', ClearChildChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()
        ;
    }
}

/**
 * Same as ClearChildParentDefinition, but the parent resource declares an
 * identifier of its own.
 */
class ClearChildIdentifiedParentDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(ClearChildParent::class);

        $this
            ->identifier('id')
                ->int()

            ->relationship('child', ClearChildChildDefinition::class)
                ->one()
                ->linkable()
                ->visible()
        ;
    }
}

class ClearChildEntityFactory implements EntityFactory
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
