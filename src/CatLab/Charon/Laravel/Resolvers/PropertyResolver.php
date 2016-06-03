<?php

namespace CatLab\Charon\Laravel\Resolvers;

use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Values\Base\RelationshipValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            $child = call_user_func_array(array($entity, $name), $getterParameters);

            if ($child instanceof BelongsTo) {
                $child = $child->get()->first();
            }

            return $child;
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
            if ($field->getRecords()) {
                $models->take($field->getRecords());
            }

            $models = $models->get();
        }

        return $transformer->toResources(
            $field->getChildResource(),
            $models,
            $context->getChildContext($field, $field->getExpandContext()),
            $value,
            $entity
        );
    }
}