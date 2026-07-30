<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

use CatLab\Charon\Models\Properties\RelationshipField;

/**
 * Class ClientReferenceMap
 *
 * Request-scoped registry of client-supplied references ('$ref' payload keys).
 * It lets one write payload create several sibling resources that link to each
 * other before any of them has a database id.
 *
 * A resource carrying "$ref": "tmp-1" registers its (newly created) entity
 * under 'tmp-1' via register(). A sibling resource that links to it purely by
 * "$ref": "tmp-1" (no other attributes) resolves it via resolve() -- either
 * immediately, or, if the referenced entity hasn't been created yet, as a
 * pending link that a consumer resolves once the whole payload has been
 * walked.
 *
 * Framework-agnostic: no illuminate dependencies.
 *
 * @package CatLab\Charon\Models
 */
class ClientReferenceMap
{
    /**
     * @var array<string, mixed>
     */
    private array $mapping = [];

    /**
     * @var array<string, callable[]>
     */
    private array $deferred = [];

    /**
     * @var array<int, array{parent: mixed, field: RelationshipField, ref: string}>
     */
    private array $pendingLinks = [];

    /**
     * Register the entity that was created for a given client reference.
     * @param string $ref
     * @param mixed $entity
     * @return void
     */
    public function register(string $ref, $entity): void
    {
        $this->mapping[$ref] = $entity;
    }

    /**
     * Resolve a client reference to its registered entity, or null if it
     * hasn't been registered (yet).
     * @param string $ref
     * @return mixed|null
     */
    public function resolve(string $ref)
    {
        return $this->mapping[$ref] ?? null;
    }

    /**
     * Queue a callback to run once $ref becomes resolvable. If $ref is
     * already registered, the callback still only runs on the next
     * runDeferred() call, not immediately.
     * @param string $ref
     * @param callable $apply function (mixed $entity): void
     * @return void
     */
    public function defer(string $ref, callable $apply): void
    {
        $this->deferred[$ref][] = $apply;
    }

    /**
     * Every ref that still has one or more deferred callbacks waiting.
     * @return array<string, callable[]>
     */
    public function getUnresolvedDeferred(): array
    {
        return $this->deferred;
    }

    /**
     * Run every deferred callback whose ref can now be resolved, and forget
     * them. Returns the list of refs that are still unresolved.
     * @return string[]
     */
    public function runDeferred(): array
    {
        $unresolved = [];

        foreach ($this->deferred as $ref => $callbacks) {
            $entity = $this->resolve($ref);

            if ($entity !== null) {
                foreach ($callbacks as $callback) {
                    $callback($entity);
                }
                unset($this->deferred[$ref]);
            } else {
                $unresolved[] = $ref;
            }
        }

        return $unresolved;
    }

    /**
     * Record a relationship link that could not be resolved immediately
     * (the referenced entity does not exist in the map yet). Charon itself
     * cannot apply this: it has no PropertySetter at this point in the
     * pipeline. The consumer is expected to drain getPendingLinks() once the
     * whole payload has been processed and resolve each 'ref' against this
     * map to apply the link with its own PropertySetter.
     * @param mixed $parent
     * @param RelationshipField $field
     * @param string $ref
     * @return void
     */
    public function addPendingLink($parent, RelationshipField $field, string $ref): void
    {
        $this->pendingLinks[] = [
            'parent' => $parent,
            'field' => $field,
            'ref' => $ref,
        ];
    }

    /**
     * @return array<int, array{parent: mixed, field: RelationshipField, ref: string}>
     */
    public function getPendingLinks(): array
    {
        return $this->pendingLinks;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * Whether this map has ever seen any activity (registrations, deferred
     * callbacks, or pending links).
     * @return bool
     */
    public function hasAny(): bool
    {
        return $this->mapping !== [] || $this->deferred !== [] || $this->pendingLinks !== [];
    }
}
