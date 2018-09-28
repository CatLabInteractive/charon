<?php

namespace CatLab\Charon\Models\Values\Base;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class PropertyValue
 * @package CatLab\RESTResource\Models\Values\Base
 */
abstract class Value
{
    /**
     * @var Field
     */
    protected $field;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var bool
     */
    protected $visible;

    /**
     * PropertyValue constructor.
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param array $out
     */
    public function addToArray(array &$out)
    {
        $out[$this->field->getDisplayName()] = $this->toArray();
    }

    /**
     * Set a value in an entity
     * @param $entity
     * @param ResourceTransformer $resourceTransformer
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     * @param EntityFactory $factory
     * @param Context $context
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function toEntity(
        $entity,
        ResourceTransformer $resourceTransformer,
        PropertyResolver $propertyResolver,
        PropertySetter $propertySetter,
        EntityFactory $factory,
        Context $context
    ) {
        if ($this->field->canSetProperty()) {

            $value = $this->value;
            if ($transformer = $this->getField()->getTransformer()) {
                $value = $transformer->toEntityValue($value, $context);
            }

            $propertySetter->setEntityValue(
                $resourceTransformer,
                $entity,
                $this->field,
                $value,
                $context
            );
        }
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function equals($value)
    {
        return $this->value == $value;
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisible($visible = false)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param Context $context
     * @param string $path
     * @return
     */
    abstract public function validate(Context $context, string $path);
}