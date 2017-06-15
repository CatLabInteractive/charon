<?php

namespace CatLab\Charon\Factories;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Models\Identifier;
use Exception;

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
    public function resolveLinkedEntity($parent, string $entityClassName, array $identifiers, Context $context)
    {
        if (!isset($identifiers['id'])) {
            throw new Exception('No ID identifier found for ' . $entityClassName);
        }

        return $entityClassName::find($identifiers['id']);
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
        $data = $identifier->toArray();

        if (!isset($data['id'])) {
            throw new Exception('No ID identifier found for ' . $entityClassName);
        }

        return $entityClassName::find($data['id']);
    }
}