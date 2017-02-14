<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Values\Base\Value;
use CatLab\Charon\Models\Values\ChildrenValue;
use CatLab\Charon\Models\Values\ChildValue;
use CatLab\Charon\Models\Values\LinkValue;
use CatLab\Charon\Models\Values\PropertyValue;

/**
 * Class PropertyValues
 * @package CatLab\RESTResource\Collections
 */
class PropertyValueCollection
{
    /**
     * @var PropertyValue[]
     */
    private $values;

    /**
     * PropertyValues constructor.
     */
    public function __construct()
    {
        $this->values = [];
    }

    /**
     * @param Value $value
     * @return $this
     */
    public function add(Value $value)
    {
        $key = spl_object_hash($value->getField());

        $this->values[$key] = $value;
        return $this;
    }

    /**
     * @param Field $resourceField
     * @return PropertyValue
     */
    public function touchProperty(Field $resourceField)
    {
        return $this->touchPropertyValue($resourceField, PropertyValue::class);
    }

    /**
     * @param Field $resourceField
     * @return PropertyValue|null
     */
    public function getProperty(Field $resourceField)
    {
        $key = spl_object_hash($resourceField);

        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return null;
    }

    /**
     * @param Field $resourceField
     * @return LinkValue
     */
    public function getLink(Field $resourceField)
    {
        return $this->touchPropertyValue($resourceField, LinkValue::class);
    }

    /**
     * @param Field $resourceField
     * @return ChildrenValue
     */
    public function getChildren(Field $resourceField)
    {
        return $this->touchPropertyValue($resourceField, ChildrenValue::class);
    }

    /**
     * @param Field $resourceField
     * @return ChildValue
     */
    public function getChild(Field $resourceField)
    {
        return $this->touchPropertyValue($resourceField, ChildValue::class);
    }

    /**
     * @return PropertyValue[]
     */
    public function getValues()
    {
        return array_values($this->values);
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function clear(Field $field)
    {
        $key = spl_object_hash($field);
        if (isset($this->values[$key])) {
            unset ($this->values[$key]);
        }
        return $this;
    }

    /**
     * @param Field $resourceField
     * @param $propertyValueClass
     * @return PropertyValue
     */
    private function touchPropertyValue(Field $resourceField, $propertyValueClass)
    {
        $key = spl_object_hash($resourceField);

        if (!isset($this->values[$key])) {
            $this->add(new $propertyValueClass($resourceField));
        }

        return $this->values[$key];
    }

    /**
     * @return PropertyValueCollection
     */
    public function getIdentifiers()
    {
        $out = new self();
        foreach ($this->getValues() as $v) {
            if ($v->getField() instanceof IdentifierField) {
                $out->values[] = $v;
            }
        }

        return $out;
    }

    /**
     * @param string $name
     * @return PropertyValue
     */
    public function getFromName(string $name)
    {
        foreach ($this->getValues() as $v) {
            if ($v->getField()->getName() === $name) {
                return $v;
            }
        }
        return null;
    }

    /**
     * @return array|\CatLab\Charon\Models\Values\Base\Value[]
     */
    public function toMap()
    {
        $out = [];
        foreach ($this->getValues() as $value) {
            $out[$value->getField()->getName()] = $value->getValue();
        }

        return $out;
    }
}