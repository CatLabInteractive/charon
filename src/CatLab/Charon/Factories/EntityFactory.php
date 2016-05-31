<?php

namespace CatLab\Charon\EntityFactory;
use CatLab\Charon\Interfaces\Context;
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
    public function resolveLinkedEntity($parent, $entityClassName, array $identifiers, Context $context)
    {
        if (!isset($identifiers['id'])) {
            throw new Exception('No ID identifier found for ' . $entityClassName);
        }

        return $entityClassName::find($identifiers['id']);
    }
}