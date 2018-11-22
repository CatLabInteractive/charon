<?php

namespace CatLab\Charon\Models\Properties\Base;

use CatLab\Charon\Interfaces\ResourceDefinitionManipulator;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Requirements\Interfaces\Validator;

/**
 * Class PropertyGroup
 *
 * All methods called upon a property group will be set to all grouped parameters.
 *
 * @package CatLab\Charon\Models\Properties\Base
 */
class PropertyGroup implements ResourceDefinitionManipulator
{
    /**
     * @var ResourceDefinition
     */
    private $resourceDefinition;

    /**
     * @var Field[]
     */
    private $properties;

    /**
     * PropertyGroup constructor.
     * @param ResourceDefinition $resourceDefinition
     * @param array $properties
     */
    public function __construct(ResourceDefinition $resourceDefinition, array $properties)
    {
        $this->resourceDefinition = $resourceDefinition;
        $this->properties = $properties;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        foreach ($this->properties as $v) {
            call_user_func_array([ $v, $name ], $arguments);
        }

        return $this;
    }

    /**
     * Finish this field and start a new one
     * @param string $name
     * @return ResourceField
     */
    public function field($name)
    {
        return $this->resourceDefinition->field($name);
    }

    /**
     * Finish this field and start a new one
     * @param array $fields
     * @return ResourceField
     */
    public function fields(array $fields)
    {
        return $this->resourceDefinition->field($fields);
    }

    /**
     * Finish this field and start a new one
     * @param string $name
     * @param string $resourceDefinitionClass
     * @return RelationshipField
     */
    public function relationship($name, $resourceDefinitionClass) : RelationshipField
    {
        return $this->resourceDefinition->relationship($name, $resourceDefinitionClass);
    }

    /**
     * @param Validator $validator
     * @return ResourceDefinitionManipulator
     */
    public function validator(Validator $validator) : ResourceDefinitionManipulator
    {
        return $this->resourceDefinition->validator($validator);
    }
}