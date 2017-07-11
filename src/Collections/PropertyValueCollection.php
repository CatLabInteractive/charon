<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Values\Base\Value;
use CatLab\Charon\Models\Values\ChildrenValue;
use CatLab\Charon\Models\Values\ChildValue;
use CatLab\Charon\Models\Values\LinkValue;
use CatLab\Charon\Models\Values\PropertyValue;

/**
 * Class PropertyValues
 * @package CatLab\RESTResource\Collections
 */
class PropertyValueCollection extends Collection
{
    /**
     * @param Value $value
     * @return $this
     */
    public function add($value)
    {
        $key = spl_object_hash($value->getField());

        $this[$key] = $value;
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

        if (isset($this[$key])) {
            return $this[$key];
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
        return array_values($this->toArray());
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function clear(Field $field)
    {
        $key = spl_object_hash($field);
        if (isset($this[$key])) {
            unset ($this[$key]);
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

        if (!isset($this[$key])) {
            $this->add(new $propertyValueClass($resourceField));
        }

        return $this[$key];
    }

    /**
     * @return PropertyValueCollection
     */
    public function getIdentifiers()
    {
        return $this->filter(
            function(Value $v) {
                return $v->getField() instanceof IdentifierField;
            }
        );
    }

    /**
     * Returns all (plain) resource fields.
     * @return PropertyValueCollection
     */
    public function getResourceFields()
    {
        return $this->filter(
            function(Value $v) {
                return $v->getField() instanceof ResourceField;
            }
        );
    }

    /**
     * Return all relationship fields.
     * @return PropertyValueCollection
     */
    public function getRelationships()
    {
        return $this->filter(
            function(Value $v) {
                return $v->getField() instanceof RelationshipField;
            }
        );
    }

    /**
     * @param string $name
     * @return PropertyValue
     */
    public function getFromName(string $name)
    {
        return $this->filter(
            function(Value $v) use ($name) {
                return $v->getField()->getName() === $name;
            }
        )->first();
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