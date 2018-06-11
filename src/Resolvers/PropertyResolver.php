<?php

namespace CatLab\Charon\Laravel\Resolvers;

use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class PropertyResolver
 * @package CatLab\RESTResource\Laravel\Resolvers
 */
class PropertyResolver extends \CatLab\Charon\Resolvers\PropertyResolver
{
    /**
     * @param mixed $entity
     * @param string $name
     * @param mixed[] $getterParameters
     * @return mixed
     */
    protected function getValueFromEntity($entity, $name, array $getterParameters)
    {
        // Check for get method
        if (method_exists($entity, 'get'.ucfirst($name))) {
            return call_user_func_array(array($entity, 'get'.ucfirst($name)), $getterParameters);
        }

        // Check for laravel "relationship" method
        elseif (method_exists($entity, $name)) {

            if (
                $entity instanceof Model &&
                $entity->relationLoaded($name)
            ) {
                return $entity->$name;
            } else {

                $child = call_user_func_array(array($entity, $name), $getterParameters);

                if ($child instanceof BelongsTo) {
                    $child = $child->get()->first();
                } elseif ($child instanceof HasOne) {
                    $child = $child->get()->first();
                }

                return $child;
            }
        }

        elseif (method_exists($entity, 'is'.ucfirst($name))) {
            return call_user_func_array(array($entity, 'is'.ucfirst($name)), $getterParameters);
        }

        else {
            //throw new InvalidPropertyException;
            return $entity->$name;
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipValue $value
     * @param Context $context
     * @return ResourceCollection
     * @throws InvalidPropertyException
     */
    public function resolveManyRelationship(
        ResourceTransformer $transformer,
        $entity,
        RelationshipValue $value,
        Context $context
    ) : ResourceCollection {

        $field = $value->getField();

        $models = $this->resolveProperty($transformer, $entity, $field, $context);

        if ($models instanceof Relation) {
            // Clone to avoid setting multiple filters
            $models = clone $models;

            if ($field->getRecords()) {
                $models->take($field->getRecords());
            }

            // Handle the order
            $orderBys = $field->getOrderBy();
            foreach ($orderBys as $orderBy) {
                $sortField = $field->getChildResource()->getFields()->getFromDisplayName($orderBy[0]);
                if ($sortField) {
                    $models->orderBy($transformer->getQualifiedName($sortField, $orderBy[1]));
                }
            }

            $models = $models->get();
        }

        if ($models === null) {
            return new ResourceCollection();
        }

        return $transformer->toResources(
            $field->getChildResource(),
            $models,
            $context->getChildContext($field, $field->getExpandContext()),
            $value,
            $entity
        );
    }

    /**
     * @param ResourceTransformer $transformer
     * @param RelationshipField $field
     * @param mixed $parentEntity
     * @param PropertyValueCollection $identifiers
     * @param Context $context
     * @return mixed
     */
    public function getChildByIdentifiers(
        ResourceTransformer $transformer,
        RelationshipField $field,
        $parentEntity,
        PropertyValueCollection $identifiers,
        Context $context
    ) {
        $entities = $this->resolveProperty($transformer, $parentEntity, $field, $context);

        if ($entities instanceof Relation) {
            // Clone to avoid setting multiple filters
            $entities = clone $entities;
            foreach ($identifiers->toMap() as $k => $v) {
                $entities->where($k, $v);
            }

            $entity = $entities->get()->first();
            if (!$entity) {
                return null;
            }
            return $entity;
        }

        foreach ($entities as $entity) {
            if ($this->entityEquals($transformer, $entity, $identifiers, $context)) {
                return $entity;
            }
        }
    }

    /**
     * @param Field $field
     * @return string
     */
    public function getQualifiedName(Field $field)
    {
        $name = $field->getResourceDefinition()->getEntityClassName();
        $obj = new $name;

        return $obj->getTable() . '.' . $field->getName();
    }
}