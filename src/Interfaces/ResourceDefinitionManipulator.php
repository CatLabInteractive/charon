<?php

namespace CatLab\Charon\Interfaces;

use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Requirements\Interfaces\Validator;

/**
 * Class ResourceManipulator
 * @package CatLab\Charon\Interfaces
 */
interface ResourceDefinitionManipulator
{
    /**
     * Finish this field and start a new one
     * @param string $name
     * @return ResourceField
     */
    public function field($name);

    /**
     * Finish this field and start a new one
     * @param array $fields
     * @return ResourceField
     */
    public function fields(array $fields);

    /**
     * Finish this field and start a new one
     * @param string $name
     * @param string $resourceDefinitionClass
     * @return RelationshipField
     */
    public function relationship($name, $resourceDefinitionClass) : RelationshipField;

    /**
     * Add a validator
     * @param Validator $validator
     * @return ResourceDefinitionManipulator
     */
    public function validator(Validator $validator) : ResourceDefinitionManipulator;
}