<?php

namespace CatLab\Charon\Resolvers;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Interfaces\Database\SelectQueryParameters;
use CatLab\Base\Models\Database\WhereParameter;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Exceptions\ValueUndefined;
use CatLab\Charon\Exceptions\VariableNotFoundInContext;
use CatLab\Charon\Interfaces\DynamicContext;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;
use CatLab\Charon\Models\Values\PropertyValue;
use ReflectionMethod;

/**
 * Class PropertyResolver
 * @package CatLab\RESTResource\Resolvers
 */
class PropertyResolver extends ResolverBase implements \CatLab\Charon\Interfaces\PropertyResolver
{
    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param Field $field
     * @param Context $context
     * @return mixed
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    public function resolveProperty(ResourceTransformer $transformer, $entity, Field $field, Context $context)
    {
        $path = $this->splitPathParameters($field->getName());
        return $this->resolveChildPath($transformer, $entity, $path, $field, $context);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipValue $value
     * @param Context $context
     * @return \CatLab\Charon\Interfaces\ResourceCollection
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    public function resolveManyRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipValue $value,
        Context $context
    ) : ResourceCollection {

        $field = $value->getField();

        $childResource = $field->getChildResource();
        $childContext = $context->getChildContext($field, $field->getExpandContext());

        $children = $this->resolveProperty($transformer, $entity, $field, $childContext);
        return $transformer->toResources($childResource, $children, $context, $value, $entity);

    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipValue $value
     * @param Context $context
     * @return \CatLab\Charon\Interfaces\RESTResource
     * @throws VariableNotFoundInContext
     */
    public function resolveOneRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipValue $value,
        Context $context
    ) {
        $field = $value->getField();

        $child = null;
        try {
            $child = $this->resolveProperty($transformer, $entity, $field, $context);
        } catch (InvalidPropertyException $e) {
            return null;
        }

        if ($child) {
            return $transformer->toResource(
                $field->getChildResource(),
                $child,
                $context->getChildContext($field, $field->getExpandContext()),
                $value,
                $entity
            );
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param &$input
     * @param Field $field
     * @param Context $context
     * @return mixed
     * @throws ValueUndefined
     */
    public function resolvePropertyInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    ) {
        if (!array_key_exists($field->getDisplayName(), $input)) {
            throw ValueUndefined::make($field->getDisplayName());
        }
        return $input[$field->getDisplayName()];
    }

    /**
     * Check if input contains data.
     * @param ResourceTransformer $transformer
     * @param $input
     * @param Field $field
     * @param Context $context
     * @return bool
     */
    public function hasPropertyInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    ): bool {
        return array_key_exists($field->getDisplayName(), $input);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed &$input ,
     * @param RelationshipField $field
     * @param Context $context
     * @return \CatLab\Charon\Interfaces\ResourceCollection
     */
    public function resolveManyRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    ) : ResourceCollection {

        $out = $transformer->getResourceFactory()->createResourceCollection();

        $children = $this->resolveChildrenListInput($transformer, $input, $field, $context);
        if ($children) {
            foreach ($children as $child) {
                $childContext = $this->getInputChildContext($transformer, $field, $context);
                $out->add($transformer->fromArray($field->getChildResource(), $child, $childContext));
            }
        }

        return $out;
    }

    /**
     * Check if relationship data exists in input.
     * @param ResourceTransformer $transformer
     * @param $input
     * @param RelationshipField $field
     * @param Context $context
     * @return bool
     */
    public function hasRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    ) : bool {
        return $this->hasPropertyInput($transformer, $input, $field, $context);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed &$input ,
     * @param RelationshipField $field
     * @param Context $context
     * @return RESTResource
     */
    public function resolveOneRelationshipInput(
        ResourceTransformer $transformer,
        &$input,
        RelationshipField $field,
        Context $context
    ) {
        try {
            $child = $this->resolvePropertyInput($transformer, $input, $field, $context);

            if ($child) {
                $childContext = $this->getInputChildContext($transformer, $field, $context);
                return $transformer->fromArray($field->getChildResource(), $child, $childContext);
            }
        } catch (ValueUndefined $e) {
            // Don't worry be happy.
        }
        return null;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param RelationshipField $field
     * @param Context $context
     * @return Context
     */
    private function getInputChildContext(ResourceTransformer $transformer, RelationshipField $field, Context $context)
    {
        $childResourceDefinition = $field->getChildResource();

        // Check if we want to create a new child or edit an existing child
        if (
            $context->getAction() !== Action::CREATE &&
            $field->canCreateNewChildren() &&
            $this->hasInputIdentifier($transformer, $childResourceDefinition, $context, $input)
        ) {
            $action = Action::EDIT;
        } else {
            $action = Action::CREATE;
        }

        $childContext = $context->getChildContext($field, $action);

        return $childContext;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param RelationshipField $field
     * @param mixed $parentEntity
     * @param PropertyValueCollection $identifiers
     * @param Context $context
     * @return mixed
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    public function getChildByIdentifiers(
        ResourceTransformer $transformer,
        RelationshipField $field,
        $parentEntity,
        PropertyValueCollection $identifiers,
        Context $context
    ) {
        $entities = $this->resolveProperty($transformer, $parentEntity, $field, $context);
        foreach ($entities as $entity) {
            if ($this->entityEquals($transformer, $entity, $identifiers, $context)) {
                return $entity;
            }
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RESTResource $resource
     * @param Context $context
     * @return bool
     * @throws InvalidPropertyException
     * @throws VariableNotFoundInContext
     */
    public function doesResourceRepresentEntity(
        ResourceTransformer $transformer,
        $entity,
        RESTResource $resource,
        Context $context
    ) : bool {
        return $this->entityEquals($transformer, $entity, $resource->getProperties()->getIdentifiers(), $context);
    }

    /**
     * @param Field $field
     * @return string
     */
    public function getQualifiedName(Field $field)
    {
        return $field->getResourceDefinition()->getEntityClassName() . '.' . $field->getName();
    }

    /**
     * Return TRUE if the input has an id, and thus is an edit of an existing field.
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $resourceDefinition
     * @param Context $context
     * @param $input
     * @return bool
     */
    protected function hasInputIdentifier(
        ResourceTransformer $transformer,
        ResourceDefinition $resourceDefinition,
        Context $context,
        &$input
    ) {
        $identifiers = $resourceDefinition->getFields()->getIdentifiers();
        if (count($identifiers) > 0) {
            foreach ($identifiers as $field) {
                try {
                    $value = $this->resolvePropertyInput($transformer, $input, $field, $context);
                    if (!$value) {
                        return false;
                    }
                } catch (ValueUndefined $e) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $input
     * @param Field $field
     * @param Context $context
     * @return null
     */
    protected function resolveChildrenListInput(
        ResourceTransformer $transformer,
        &$input,
        Field $field,
        Context $context
    ) {
        try {
            $children = $this->resolvePropertyInput($transformer, $input, $field, $context);
        } catch (ValueUndefined $e) {
            return null;
        }


        if (
            $children &&
            isset($children[ResourceTransformer::RELATIONSHIP_ITEMS]) &&
            is_array($children[ResourceTransformer::RELATIONSHIP_ITEMS])

        ) {
            return $children[ResourceTransformer::RELATIONSHIP_ITEMS];
        }
        return null;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $entityCollection
     * @param RelationshipField $field
     * @param Context $context
     * @return void
     */
    public function eagerLoadRelationship(
        ResourceTransformer $transformer,
        $queryBuilder,
        RelationshipField $field,
        Context $context
    ) {
        $this->callEntitySpecificMethodIfExists(
            $transformer,
            $field,
            $context,
            self::EAGER_LOAD_METHOD_PREFIX,
            [
                $queryBuilder
            ]
        );
    }

    /**
     * Apply a filter to a query builder.
     * (Used for filtering or searching entries on filterable/searchble fields)
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param Field $field
     * @param SelectQueryParameters $queryBuilder
     * @param $value
     * @param string $operator
     * @return void
     */
    public function applyPropertyFilter(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        Field $field,
        SelectQueryParameters $queryBuilder,
        $value,
        $operator = Operator::EQ
    ) {
        // do we have a specific 'filter' method?
        if ($this->callEntitySpecificMethodIfExists(
                $transformer,
                $field,
                $context,
                self::FILTER_METHOD_PREFIX,
                [
                    $queryBuilder,
                    $value,
                    $operator,
                    $definition->getEntityClassName()
                ]
            )
        ) {
            return;
        }

        // nope? Too bad, use the regular filter method.
        $queryBuilder->where(
            new WhereParameter(
                $field->getName(),
                $operator,
                $value,
                $definition->getEntityClassName())
        );
    }

    /**
     * @param $request
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getParameterFromRequest($request, string $key, $default = null)
    {
        return isset($request[$key]) ? $request[$key] : $default;
    }
}
