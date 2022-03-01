<?php

namespace CatLab\Charon\Factories;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Models\Identifier;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Class EntityFactory
 * @package CatLab\RESTResource\EntityFactory
 */
class EntityFactory implements \CatLab\Charon\Interfaces\EntityFactory
{
    /**
     * @param $entityClassName
     * @param Context $context
     * @return mixed
     */
    public function createEntity($entityClassName, Context $context)
    {
        return new $entityClassName;
    }

    /**
     * @param $parent
     * @param $entityClassName
     * @param array $identifiers
     * @param Context $context
     * @return mixed
     * @throws Exception
     */
    public function resolveLinkedEntity($parent, string $entityClassName, Identifier $identifier, Context $context)
    {
        $identifierValues = $identifier->getIdentifiers()->transformToEntityValuesMap($context);

        if (isset($identifierValues['id'])) {
            return $this->getAuthorizedResolvedEntity($entityClassName::find($identifierValues['id']));
        }

        if (count($identifierValues) === 0) {
            return null;
        }

        $query = $entityClassName::query();
        foreach ($identifierValues as $k => $v) {
            $query->where($k, '=', $v);
        }

        return $this->getAuthorizedResolvedEntity($query->first());
    }

    /**
     * @param string $entityClassName
     * @param Identifier $identifier
     * @param Context $context
     * @return mixed
     * @throws Exception
     */
    public function resolveFromIdentifier(string $entityClassName, Identifier $identifier, Context $context)
    {
        $identifierValues = $identifier->getIdentifiers()->transformToEntityValuesMap($context);

        if (isset($identifierValues['id'])) {
            return $this->getAuthorizedResolvedEntity($entityClassName::find($identifierValues['id']));
        }

        if (count($identifierValues) === 0) {
            return null;
        }

        $query = $entityClassName::query();
        foreach ($identifierValues as $k => $v) {
            $query->where($k, '=', $v);
        }

        return $this->getAuthorizedResolvedEntity($query->first());
    }

    /**
     * @param $entity
     * @return mixed
     */
    protected function getAuthorizedResolvedEntity($entity)
    {
        // By default, no authorization is done.
        return $entity;
    }
}
