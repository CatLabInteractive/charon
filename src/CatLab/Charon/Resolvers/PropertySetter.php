<?php

namespace CatLab\Charon\Resolvers;

use CatLab\Charon\Collections\PropertyValues;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\PropertyResolver as PropertyResolverContract;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class PropertySetter
 * @package CatLab\RESTResource\Resolvers
 */
class PropertySetter extends ResolverBase implements \CatLab\Charon\Interfaces\PropertySetter
{
    /***********************************************************
     * Quick start: overwrite these to match your framework
     **********************************************************/

    /**
     * @param $entity
     * @param $name
     * @param array $childEntities
     * @param $parameters
     * @throws InvalidPropertyException
     */
    protected function addChildrenToEntity($entity, $name, array $childEntities, $parameters = [])
    {
        // Check for add method
        if (method_exists($entity, 'add'.ucfirst($name))) {
            array_unshift($parameters, $childEntities);
            call_user_func_array(array($entity, 'add'.ucfirst($name)), $parameters);
        } else {
            throw InvalidPropertyException::create($name, get_class($entity));
        }
    }

    /**
     * @param $entity
     * @param $name
     * @param array $childEntities
     * @param $parameters
     * @throws InvalidPropertyException
     */
    protected function removeChildrenFromEntity($entity, $name, array $childEntities, $parameters = [])
    {
        // Check for add method
        if (method_exists($entity, 'remove'.ucfirst($name))) {
            array_unshift($parameters, $childEntities);
            call_user_func_array(array($entity, 'remove'.ucfirst($name)), $parameters);
        } else {
            throw InvalidPropertyException::create($name, get_class($entity));
        }
    }

    /**
     * @param $entity
     * @param $name
     * @param $value
     * @param array $setterParameters
     * @return mixed
     * @throws InvalidPropertyException
     */
    protected function setValueInEntity($entity, $name, $value, $setterParameters = [])
    {
        // Check for get method
        if (method_exists($entity, 'set'.ucfirst($name))) {
            array_unshift($setterParameters, $value);
            return call_user_func_array(array($entity, 'set'.ucfirst($name)), $setterParameters);
        }

        elseif (
            is_object($entity) &&
            property_exists($entity, $name)
        ) {
            $entity->$name = $value;
        }

        elseif (
            method_exists($entity, 'hasAttribute') &&
            call_user_func([ $entity, 'hasAttribute'], $name)
        ) {
            $entity->$name = $value;
        }

        else {
            throw InvalidPropertyException::create($name, get_class($entity));
        }
    }

    /**
     * @param $entity
     * @param $name
     * @param $value
     * @param array $setterParameters
     * @throws InvalidPropertyException
     */
    protected function setChildInEntity($entity, $name, $value, $setterParameters = [])
    {
        $this->setValueInEntity($entity, $name, $value, $setterParameters);
    }

    /**
     * @param $entity
     * @param $name
     * @param array $setterParameters
     * @throws InvalidPropertyException
     */
    protected function clearChildInEntity($entity, $name, $setterParameters = [])
    {
        $this->setValueInEntity($entity, $name, null, $setterParameters);
    }

    /***********************************************************************
     * These should be fine
     ***********************************************************************/

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param Field $field
     * @param mixed $value
     * @param Context $context
     */
    public function setEntityValue(
        ResourceTransformer $transformer,
        $entity,
        Field $field,
        $value,
        Context $context
    ) {
        list ($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $this->setValueInEntity($entity, $name, $value, $parameters);
    }

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RelationshipField $field
     * @param $childEntities
     * @param Context $context
     * @throws InvalidPropertyException
     */
    public function addChildren(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    ) {
        list($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $this->addChildrenToEntity($entity, $name, $childEntities, $parameters);
    }

    /**
     * Add a child to a colleciton
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param RelationshipField $field
     * @param $childEntities
     * @param Context $context
     */
    public function removeChildren(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    ) {
        list($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $this->removeChildrenFromEntity($entity, $name, $childEntities, $parameters);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param mixed $value
     * @param Context $context
     */
    public function setChild(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        $value,
        Context $context
    ) {
        list ($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $this->setChildInEntity($entity, $name, $value, $parameters);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param mixed $entity
     * @param RelationshipField $field
     * @param Context $context
     */
    public function clearChild(
        ResourceTransformer $transformer,
        $entity,
        RelationshipField $field,
        Context $context
    )
    {
        list ($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $this->clearChildInEntity($entity, $name, $parameters);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $entity
     * @param Field $field
     * @param Context $context
     * @return array
     * @throws InvalidPropertyException
     */
    protected function resolvePath(
        ResourceTransformer $transformer,
        $entity,
        Field $field,
        Context $context
    ) {
        $path = $this->splitPathParameters($field->getName());

        $name = array_pop($path);

        // If the water is deep enough, we need to first fetch the corresponding entity.
        // We will NOT create the entity if it doesn't exist.
        // Relationships support this functionality. Regular setters do not.
        if (count($path) > 0) {
            $entity = $this->resolveChildPath($transformer, $entity, $path, $field, $context);
        }

        list($name, $parameters) = $this->getPropertyNameAndParameters($transformer, $name, $context, $field, $entity);

        return [ $entity, $name, $parameters ];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolverContract $propertyResolver
     * @param $entity
     * @param RelationshipField $field
     * @param PropertyValues[] $identifiers
     * @param Context $context
     * @return mixed
     */
    public function removeAllChildrenExcept(
        ResourceTransformer $transformer,
        PropertyResolverContract $propertyResolver,
        $entity,
        RelationshipField $field,
        array $identifiers,
        Context $context
    ) {
        list ($entity, $name, $parameters) = $this->resolvePath($transformer, $entity, $field, $context);
        $existingChildren = $this->getValueFromEntity($entity, $name, $parameters);

        $toRemove = [];

        // We should now have the existing children
        // And they should be iterable.
        foreach ($existingChildren as $child) {
            // Don't remove any new entities
            if (!$this->entityExists(
                $transformer,
                $child,
                $field->getResourceDefinition()->getFields()->getIdentifiers(),
                $context
            )) {
                continue 1;
            }
            
            $found = false;
            foreach ($identifiers as $identifierk => $identifier) {
                if ($this->entityEquals($transformer, $child, $identifier, $context)) {
                    $found = true;
                    unset($identifiers[$identifierk]);
                    break 1;
                }
            }

            if (!$found) {
                $toRemove[] = $child;
            }
        }

        if (count($toRemove) > 0) {
            $this->removeChildren($transformer, $entity, $field, $toRemove, $context);
        }
    }
}