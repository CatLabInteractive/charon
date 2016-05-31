<?php

namespace CatLab\Charon\Laravel\Resolvers;

use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class PropertySetter
 * @package CatLab\RESTResource\Laravel\Resolvers
 */
class PropertySetter extends \CatLab\Charon\Resolvers\PropertySetter
{
    /**
     * @param ResourceTransformer $entity
     * @param mixed $name
     * @param ResourceField $value
     * @param array $setterParameters
     */
    protected function setChildInEntity($entity, $name, $value, $setterParameters = [])
    {
        $entity->$name()->associate($value);
    }

    /**
     * @param $entity
     * @param $name
     * @param array $setterParameters
     * @throws InvalidPropertyException
     */
    protected function clearChildInEntity($entity, $name, $setterParameters = [])
    {
        $entity->$name()->dissociate();
    }

    /**
     * @param mixed $entity
     * @param string $name
     * @param mixed $value
     * @param array $setterParameters
     * @return mixed
     */
    protected function setValueInEntity($entity, $name, $value, $setterParameters = [])
    {
        // Check for set method
        if (method_exists($entity, 'set'.ucfirst($name))) {
            array_unshift($setterParameters, $value);
            return call_user_func_array(array($entity, 'set'.ucfirst($name)), $setterParameters);
        } else {
            $entity->$name = $value;
        }
    }


    /**
     * @param $entity
     * @param $name
     * @param array $childEntities
     * @param $setterParameters
     * @throws InvalidPropertyException
     */
    protected function addChildrenToEntity($entity, $name, array $childEntities, $setterParameters = [])
    {
        if (method_exists($entity, 'add'.ucfirst($name))) {
            array_unshift($setterParameters, $childEntities);
            return call_user_func_array(array($entity, 'add'.ucfirst($name)), $setterParameters);
        } else {
            foreach ($childEntities as $childEntity) {
                $entity->$name()->attach($childEntity);
            }
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
            foreach ($childEntities as $childEntity) {
                $entity->$name()->detach($childEntity);
            }
        }
    }
}