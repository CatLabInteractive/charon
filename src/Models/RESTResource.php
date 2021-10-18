<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Validation\ResourceValidator;
use CatLab\Requirements\Collections\MessageCollection;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Exceptions\RequirementValidationException;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\RESTResource as ResourceContract;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Requirements\Exceptions\ValidationException;
use CatLab\Requirements\Exceptions\ValidatorValidationException;

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
     * @var PropertyValueCollection
     */
    private $properties;

    /**
     * @var mixed
     */
    private $source;

    /**
     * Resource constructor.
     * @param ResourceDefinitionContract $resourceDefinition
     */
    public function __construct(ResourceDefinitionContract $resourceDefinition)
    {
        $this->resourceDefinition = $resourceDefinition;
        $this->properties = new PropertyValueCollection();
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
     * @param Context $context
     * @param Field $field
     * @param string $link
     * @param bool $visible
     * @return $this;
     */
    public function setLink(Context $context, Field $field, $link, $visible)
    {
        $this->properties
            ->getLink($field)
            ->setLink($link)
            ->setVisible($visible);

        return $this;
    }

    /**
     * @param Field $field
     * @return \CatLab\Charon\Models\Values\ChildValue
     */
    public function touchChildProperty(Field $field)
    {
        return $this->properties->getChild($field);
    }

    /**
     * @param Field $field
     * @return \CatLab\Charon\Models\Values\ChildrenValue
     */
    public function touchChildrenProperty(Field $field)
    {
        return $this->properties->getChildren($field);
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function clearProperty(Field $field, $url)
    {
        $this->properties->clear($field);
        return $this;
    }

    /**
     * @param Field $field
     * @param $url
     * @param ResourceCollection $children
     * @param bool $visible
     * @param ContextContract $context
     * @return $this
     */
    public function setChildrenProperty(
        ContextContract $context,
        Field $field,
        $url,
        ResourceCollection $children,
        $visible
    ) {
        $childProperty = $this->properties->getChildren($field);

        if ($url) {
            $childProperty->setUrl($url);
        }

        $childProperty->setChildren($children);
        $childProperty->setVisible($visible);
        $childProperty->setContext($context);

        return $this;
    }

    /**
     * @param Field $field
     * @param $url
     * @param ResourceContract $child
     * @param bool $visible
     * @param ContextContract $context
     * @return $this
     */
    public function setChildProperty(
        ContextContract $context,
        Field $field,
        $url,
        ResourceContract $child = null,
        $visible = true
    ) {
        $childProperty = $this->properties->getChild($field);
        $childProperty->setVisible($visible);
        $childProperty->setContext($context);

        if ($url) {
            $childProperty->setUrl($url);
        }

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
     * @return PropertyValueCollection
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
        // No identifiers found? Then all entries are always new... unless the field is linkable.
        if ($identifiers = $this->getProperties()->getIdentifiers()->count() === 0) {
            return true;
        }

        $identifiers = $this->getProperties()->getIdentifiers()->getValues();
        foreach ($identifiers as $identifier) {
            if (!$identifier->getValue()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return PropertyValueCollection
     */
    public function getIdentifiers()
    {
        return $this->getProperties()->getIdentifiers();
    }

    /**
     * @param $source
     * @return $this
     */
    public function setSource(&$source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Return the source object of the resource.
     * This way you can use the entity of a resource in a post processor.
     * The source should never leave the server.
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getResourceDefinition()->getType();
    }

    /**
     * @param ContextContract $context
     * @param null $original
     * @param string $path
     * @param bool $validateNonProvidedFields
     * @return mixed
     * @throws RequirementValidationException
     * @throws ResourceValidationException
     * @throws ValidationException
     */
    public function validate(
        ContextContract $context,
        $original = null,
        CurrentPath $path = null,
        bool $validateNonProvidedFields = true
    ) {
        if ($path === null) {
            $path = new CurrentPath();
        }

        $messages = new MessageCollection();

        foreach ($this->getResourceDefinition()->getFields() as $field) {
            $this->validateField($field, $context, $original, $path, $validateNonProvidedFields, $messages);
        }

        // Also check all resource wide requirements
        foreach ($this->getResourceDefinition()->getValidators() as $validator) {
            try {
                if ($validator instanceof ResourceValidator) {
                    $validator->setOriginal($original);
                }
                $validator->validate($this);
            } catch(ValidatorValidationException $e) {
                $validator = $e->getValidator();
                if (!$validator) {
                    throw new ValidationException('ValidatorValidationException thrown without validator attached.', 400, $e);
                }
                $messages->add($validator->getErrorMessage($e));
            } catch(RequirementValidationException $e) {
                throw $e;
            }
        }

        if (count($messages) > 0) {
            throw ResourceValidationException::make($messages);
        }
    }

    /**
     * @param Field $field
     * @param ContextContract $context
     * @param $original
     * @param CurrentPath $path
     * @param bool $validateNonProvidedFields
     * @param MessageCollection $messages
     */
    protected function validateField(
        Field $field,
        ContextContract $context,
        $original,
        CurrentPath $path,
        bool $validateNonProvidedFields,
        MessageCollection $messages
    ) {
        // Is field applicable?
        if (!$field->hasAction($context->getAction())) {
            return;
        }

        $value = $this->properties->getProperty($field);

        try {
            if (!isset($value)) {
                if ($validateNonProvidedFields || $field->shouldAlwaysValidate()) {
                    $field->validate(null, $path, $validateNonProvidedFields);
                }
            } else {
                $value->validate($context, $path, $validateNonProvidedFields);
            }
        } catch(PropertyValidationException $e) {
            $messages->merge($e->getMessages());
        }
    }
}
