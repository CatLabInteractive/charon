<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\Context;
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
        $value = $this->touchPropertyValue($resourceField, PropertyValue::class);
        if (!$value instanceof PropertyValue) {
            throw new \InvalidArgumentException('touchProperty must return a ' . LinkValue::class . ' model.');
        }

        return $value;
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
        $value = $this->touchPropertyValue($resourceField, LinkValue::class);
        if (!$value instanceof LinkValue) {
            throw new \InvalidArgumentException('getLink must return a ' . LinkValue::class . ' model.');
        }

        return $value;
    }

    /**
     * @param Field $resourceField
     * @return ChildrenValue
     */
    public function getChildren(Field $resourceField)
    {
        $value = $this->touchPropertyValue($resourceField, ChildrenValue::class);
        if (!$value instanceof ChildrenValue) {
            throw new \InvalidArgumentException('getChildren must return a ' . ChildrenValue::class . ' model.');
        }

        return $value;
    }

    /**
     * @param Field $resourceField
     * @return ChildValue
     */
    public function getChild(Field $resourceField)
    {
        $value = $this->touchPropertyValue($resourceField, ChildValue::class);
        if (!$value instanceof ChildValue) {
            throw new \InvalidArgumentException('getChildren must return a ' . ChildValue::class . ' model.');
        }

        return $value;
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
     * @return Value
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
     * @param string $name
     * @return PropertyValue
     */
    public function getFromDisplayName(string $name)
    {
        return $this->filter(
            function(Value $v) use ($name) {
                return $v->getField()->getDisplayName() === $name;
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

    /**
     * Transform the value collection to its 'entry state' (using any field specified transformers that might be
     * applicable) and return that as an array.
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function transformToEntityValuesMap(Context $context = null)
    {
        $out = [];
        foreach ($this->getValues() as $value) {
            $out[$value->getField()->getName()] = $value->getTransformedEntityValue($context);
        }

        return $out;
    }
}
