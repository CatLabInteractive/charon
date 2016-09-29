<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\ResourceDefinitionManipulator;
use CatLab\Charon\Models\Properties\Base\PropertyGroup;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Swagger\SwaggerBuilder;
use CatLab\Requirements\Collections\RequirementCollection;
use CatLab\Requirements\Collections\ValidatorCollection;
use CatLab\Requirements\Interfaces\Requirement as RequirementInterface;
use CatLab\Requirements\Interfaces\Validator;

/**
 * Class ResourceDefinition
 * @package CatLab\RESTResource\Models
 */
class ResourceDefinition implements ResourceDefinitionContract, ResourceDefinitionManipulator
{
    /**
     * @var ResourceFieldCollection
     */
    private $fields;

    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @var string
     */
    private $url;

    /**
     * @var ValidatorCollection
     */
    private $validators;

    /**
     * ResourceDefinition constructor.
     * @param string $entityClassName
     */
    public function __construct($entityClassName)
    {
        $this->entityClassName = $entityClassName;
        $this->fields = new ResourceFieldCollection();
        $this->validators = new ValidatorCollection();
    }

    /**
     * @param $name
     * @return IdentifierField
     */
    public function identifier($name)
    {
        $field = new IdentifierField($this, $name);
        $this->fields->add($field);

        return $field;
    }

    /**
     * @param string $name
     * @return ResourceField|PropertyGroup
     */
    public function field($name)
    {
        if (is_array($name)) {
            $fields = [];
            foreach ($name as $v) {
                $field = new ResourceField($this, $v);
                $fields[] = $field;
                $this->fields->add($field);
            }
            return new PropertyGroup($this, $fields);
        } else {
            $field = new ResourceField($this, $name);
            $this->fields->add($field);

            return $field;
        }
    }

    /**
     * @param string $name
     * @param string $resourceDefinition
     * @return RelationshipField
     */
    public function relationship($name, $resourceDefinition) : RelationshipField
    {
        $field = new RelationshipField($this, $name, $resourceDefinition);
        $this->fields->add($field);
        
        return $field;
    }

    /**
     * @return string
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * @return ResourceFieldCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param Validator $validator
     * @return ResourceDefinitionManipulator
     */
    public function validator(Validator $validator) : ResourceDefinitionManipulator
    {
        $this->validators->add($validator);
        return $this;
    }

    /**
     * @return ValidatorCollection
     */
    public function getValidators() : ValidatorCollection
    {
        return $this->validators;
    }

    /**
     * @param SwaggerBuilder $builder
     * @param string $action
     * @return mixed[]
     */
    public function toSwagger(SwaggerBuilder $builder, $action)
    {
        $out = [
            'type' => 'object',
            'properties' => []
        ];

        foreach ($this->getFields() as $field) {
            /** @var ResourceField $field */
            if ($field->hasAction($action)) {
                $out['properties'][$field->getDisplayName()] = $field->toSwagger($builder, $action);
            }
        }

        return $out;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }
}