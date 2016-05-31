<?php

namespace CatLab\Charon\Models;

use CatLab\Requirements\Collections\MessageCollection;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use CatLab\Charon\Collections\PropertyValues;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\RESTResource as ResourceContract;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\ResourceField;

/**
 * Class Resource
 * @package CatLab\RESTResource\Models
 */
class RESTResource implements ResourceContract
{
    /**
     * @var ResourceDefinitionContract
     */
    private $resourceDefinition;

    /**
     * @var PropertyValues
     */
    private $properties;

    /**
     * Resource constructor.
     * @param ResourceDefinitionContract $resourceDefinition
     */
    public function __construct(ResourceDefinitionContract $resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
        $this->properties = new PropertyValues();
    }

    /**
     * @param Field $field
     * @param string $value
     * @param bool $visible
     * @return $this
     */
    public function setProperty(Field $field, $value, $visible)
    {
        $this->properties->touchProperty($field)->setValue($value)->setVisible($visible);
        return $this;
    }

    /**
     * @param Field $field
     * @param string $link
     * @param bool $visible
     * @return $this;
     */
    public function setLink(Field $field, $link, $visible)
    {
        $this->properties->getLink($field)->setLink($link)->setVisible($visible);
        return $this;
    }

    /**
     * @param Field $field
     * @return CatLab\Kharon\Models\Values\ChildValue
     */
    public function touchChildProperty(Field $field)
    {
        return $this->properties->getChild($field);
    }

    /**
     * @param Field $field
     * @return CatLab\Kharon\Models\Values\ChildrenValue
     */
    public function touchChildrenProperty(Field $field)
    {
        return $this->properties->getChildren($field);
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function clearProperty(Field $field)
    {
        $this->properties->clear($field);
        return $this;
    }

    /**
     * @param Field $field
     * @param ResourceCollection $children
     * @param bool $visible
     * @return $this
     */
    public function setChildrenProperty(Field $field, ResourceCollection $children, $visible)
    {
        $this->properties->getChildren($field)->setChildren($children)->setVisible($visible);
        return $this;
    }


    /**
     * @param Field $field
     * @param RESTResource $child
     * @param bool $visible
     * @return $this
     */
    public function setChildProperty(Field $field, RESTResource $child = null, $visible)
    {
        $childProperty = $this->properties->getChild($field);
        $childProperty->setVisible($visible);

        if ($child) {
            $childProperty->setChild($child);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        $out = [];

        foreach ($this->properties->getValues() as $v) {
            if ($v->isVisible()) {
                $v->addToArray($out);
            }
        }

        return $out;
    }

    /**
     * @return PropertyValues
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return ResourceDefinitionContract
     */
    public function getResourceDefinition()
    {
        return $this->resourceDefinition;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        $identifiers = $this->getProperties()->getIdentifiers()->getValues();
        foreach ($identifiers as $identifier) {
            if (!$identifier->getValue()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return CatLab\Kharon\Models\Values\PropertyValue[]
     */
    public function getIdentifiers()
    {
        return $this->getProperties()->getIdentifiers();
    }

    /**
     * @param string $path
     * @return mixed
     * @throws ResourceValidationException
     */
    public function validate(string $path = '')
    {
        $messages = new MessageCollection();

        foreach ($this->getResourceDefinition()->getFields() as $field) {
            /** @var ResourceField $field */
            $value = $this->properties->getProperty($field);

            try {
                if (!isset($value)) {
                    $field->validate(null, $path);
                } else {
                    $value->validate($path);
                }
            } catch(PropertyValidationException $e) {
                $messages->merge($e->getMessages());
            }
        }

        if (count($messages) > 0) {
            throw ResourceValidationException::make($messages);
        }
    }
}