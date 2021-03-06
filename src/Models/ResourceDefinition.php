<?php

namespace CatLab\Charon\Models;

use CatLab\Base\Helpers\ObjectHelper;
use CatLab\Base\Helpers\StringHelper;
use CatLab\Charon\Collections\ResourceFieldCollection;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\ResourceDefinitionManipulator;
use CatLab\Charon\Models\Properties\Base\PropertyGroup;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Requirements\Collections\ValidatorCollection;
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
     * @var string
     */
    private $defaultOrder;

    /**
     * @var string
     */
    private $type;

    /**
     * ResourceDefinition constructor.
     * @param string $entityClassName
     */
    public function __construct($entityClassName = null)
    {
        $this->entityClassName = $entityClassName;

        $this->fields = new ResourceFieldCollection();
        $this->validators = new ValidatorCollection();
    }

    /**
     * @return string
     */
    public function getType()
    {
        if (isset($this->type)) {
            return $this->type;
        }

        return mb_strtolower($this->getEntityName());
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
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
     * @param string|array $name
     * @return ResourceField|PropertyGroup
     */
    public function field($name)
    {
        if (is_array($name)) {
            $fields = [];
            foreach ($name as $k => $v) {
                $field = new ResourceField($this, $v);
                if (!is_int($k)) {
                    $field->setDisplayName($k);
                }
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
     * @param array $fields
     * @return PropertyGroup|ResourceField
     */
    public function fields(array $fields)
    {
        return $this->field($fields);
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
     * Similar to getEntityClassName, but instead returns a human readable name.
     * @param bool $plural
     * @return string
     */
    public function getEntityName($plural = false)
    {
        $entityClassName = $this->getEntityClassName();
        if ($entityClassName !== null) {
            $entityName = ObjectHelper::class_basename($entityClassName);
        } else {
            $entityName = ObjectHelper::class_basename($this);
        }

        if ($plural) {
            return StringHelper::plural($entityName, is_numeric($plural) ? $plural : 2);
        } else {
            return $entityName;
        }
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

    /**
     * @param string $order
     * @return $this
     */
    public function defaultOrder(string $order)
    {
        $this->defaultOrder = $order;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }
}
