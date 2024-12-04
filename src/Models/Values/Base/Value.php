<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Values\Base;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Properties\Base\Field;

/**
 * Class Value
 * @package CatLab\RESTResource\Models\Values\Base
 */
abstract class Value
{
    protected \CatLab\Charon\Models\Properties\Base\Field $field;

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
     * Return the transformed (entity) value of this field.
     * This is a shortcut for $value->getTransformer()->toEntityValue($value->getValue()), with support for child
     * relationships. As that logic gets pretty complicated quickly, this helper method aims to ease that pain.
     * @param Context $context If not provided, use Transformer::toParameterValue instead.
     * @return \CatLab\Charon\Interfaces\Transformer|mixed|null
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function getTransformedEntityValue(Context $context = null)
    {
        $value = $this->getValue();

        // Do we have a transformer?
        if (!$this->getField()->getTransformer()) {
            return $value;
        }

        if (!$context instanceof \CatLab\Charon\Interfaces\Context) {
            return $this->getField()->getTransformer()->toParameterValue($value);
        }

        return $this->getField()->getTransformer()->toEntityValue($value, $context);
        return $value;
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
    public function addToArray(array &$out): void
    {
        $displayNamePath = $this->splitParameterPath($this->field->getDisplayName());
        $displayName = array_pop($displayNamePath);

        $tmp = &$out;
        foreach ($displayNamePath as $path) {
            if (!isset($tmp[$path])) {
                $tmp[$path] = [];
            }

            $tmp = &$tmp[$path];
        }

        $tmp[$displayName] = $this->toArray();
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
    ): void {
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
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getValue() === null;
    }

    /**
     * @param Context $context
     * @param string $path
     * @param bool $validateNonProvidedFields
     * @return
     */
    abstract public function validate(Context $context, CurrentPath $path, $validateNonProvidedFields = true);

    /**
     * Split a parameter path on dot, ignore dots inside parameter
     * (example: "progress:{context.user?}.percentage" should return [ "progress:{context.user?}", "percentage" ]
     * @return string[]
     */
    protected function splitParameterPath($path)
    {
        $out = [];
        $buffer = '';
        $openBrackets = 0;

        for ($i = 0; $i < strlen($path); ++$i) {
            if ($path[$i] === '.' && $openBrackets < 1) {
                $out[] = $buffer;
                $buffer = '';
            } else {
                if ($path[$i] === '{') {
                    ++$openBrackets;
                } elseif ($path[$i] === '}') {
                    --$openBrackets;
                }

                $buffer .= $path[$i];
            }
        }

        $out[] = $buffer;
        return $out;
    }
}
