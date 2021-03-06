<?php

namespace CatLab\Charon\Models\Values\Base;

use CatLab\Charon\Exceptions\EntityNotFoundException;
use CatLab\Charon\Models\Values\ChildrenValue;
use CatLab\Requirements\Collections\MessageCollection;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Exceptions\RequirementValidationException;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use CatLab\Requirements\Exists;
use CatLab\Charon\Collections\PropertyValueCollection;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\EntityFactory;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Exceptions\ResourceException;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Base\Helpers\ObjectHelper;

/**
 * Class RelationshipValue
 * @package CatLab\RESTResource\Models\Values\Base
 */
abstract class RelationshipValue extends Value
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var Context
     */
    private $context;

    /**
     * @return RESTResource[]
     */
    abstract public function getChildren();

    /**
     * @return RESTResource[]
     */
    abstract protected function getChildrenToProcess();

    /**
     * Add a child to a collection
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     * @return
     */
    abstract protected function addChildren(
        ResourceTransformer $transformer,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    );

    /**
     * Add a child to a collection
     * @param ResourceTransformer $transformer
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param array $childEntities
     * @param Context $context
     * @return
     */
    abstract protected function editChildren(
        ResourceTransformer $transformer,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $childEntities,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     * @param $entity
     * @param RelationshipField $field
     * @param PropertyValueCollection[] $identifiers
     * @param Context $context
     * @return mixed
     */
    abstract protected function removeAllChildrenExcept(
        ResourceTransformer $transformer,
        PropertyResolver $propertyResolver,
        PropertySetter $propertySetter,
        $entity,
        RelationshipField $field,
        array $identifiers,
        Context $context
    );

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param $parent
     * @param PropertyValueCollection $identifiers
     * @param Context $context
     * @return mixed
     */
    abstract protected function getChildByIdentifiers(
        ResourceTransformer $transformer,
        PropertyResolver $propertyResolver,
        &$parent,
        PropertyValueCollection $identifiers,
        Context $context
    );

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return RelationshipValue
     */
    public function setUrl(string $url): RelationshipValue
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param Context $context
     * @return $this
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return RelationshipField
     */
    public function getField()
    {
        $field = parent::getField();
        if (!$field instanceof RelationshipField) {
            throw new \InvalidArgumentException(self::class . '::getField() must return an ' . RelationshipField::class);
        }

        return $field;
    }

    /**
     * Set a value in an entity
     * @param $parent
     * @param ResourceTransformer $resourceTransformer
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     * @param EntityFactory $factory
     * @param Context $context
     * @throws InvalidPropertyException
     * @throws EntityNotFoundException
     */
    public function toEntity(
        $parent,
        ResourceTransformer $resourceTransformer,
        PropertyResolver $propertyResolver,
        PropertySetter $propertySetter,
        EntityFactory $factory,
        Context $context
    ) {
        $children = $this->getChildrenToProcess();

        $childrenToAdd = [];
        $childrenToEdit = [];

        /**
         * Keep a list of all identifies we've touched, so we can removed those we haven't
         * @var PropertyValueCollection[] $identifiersToKeep
         */
        $identifiersToKeep = [];

        foreach ($children as $child) {
            if (!$child) {
                continue;
            }

            $this->childResourceToEntity(
                $parent,
                $child,
                $resourceTransformer,
                $propertyResolver,
                $factory,
                $context,
                $childrenToAdd,
                $childrenToEdit,
                $identifiersToKeep
            );
        }

        // Now do the actual executing
        if (count($childrenToAdd) > 0) {
            $this->addChildren(
                $resourceTransformer,
                $propertySetter,
                $parent,
                $this->getField(),
                $childrenToAdd,
                $context
            );
        }

        if (count($childrenToEdit) > 0) {
            $this->editChildren(
                $resourceTransformer,
                $propertySetter,
                $parent,
                $this->getField(),
                $childrenToEdit,
                $context
            );
        }

        // Notify the setter to remove all children that haven't been touched (in the range defined in the context)
        $this->removeAllChildrenExcept(
            $resourceTransformer,
            $propertyResolver,
            $propertySetter,
            $parent,
            $this->getField(),
            $identifiersToKeep,
            $context
        );
    }

    /**
     * @param $parent
     * @param RESTResource $child
     * @param ResourceTransformer $resourceTransformer
     * @param PropertyResolver $propertyResolver
     * @param EntityFactory $factory
     * @param Context $context
     * @param $childrenToAdd
     * @param $childrenToEdit
     * @param $identifiersToKeep
     * @throws EntityNotFoundException
     */
    private function childResourceToEntity(
        $parent,
        RESTResource $child,
        ResourceTransformer $resourceTransformer,
        PropertyResolver $propertyResolver,
        EntityFactory $factory,
        Context $context,
        &$childrenToAdd,
        &$childrenToEdit,
        &$identifiersToKeep
    ) {
        /** @var RelationshipField $field */
        $field = $this->getField();

        $childIdentifiers = $child->getProperties()->getIdentifiers();

        /** @var bool $isNew */
        $isNew = $child->isNew();

        $childEntity = null;

        if (!$isNew) {
            $childEntity = $this->getChildByIdentifiers(
                $resourceTransformer,
                $propertyResolver,
                $parent,
                $childIdentifiers,
                $context
            );

            if ($childEntity) {
                $identifiersToKeep[] = $childIdentifiers;

                // if we can't edit the child from here, there is no points in going further.
                if (!$field->canCreateNewChildren()) {
                    return;
                }
            }
        }

        // Do we just link the resource? In this case we can't edit it right now.
        if ($field->canLinkExistingEntities() && !$childEntity) {
            $entity = $factory->resolveLinkedEntity(
                $parent,
                $child->getResourceDefinition()->getEntityClassName(),
                $childIdentifiers->toMap(),
                $context
            );

            if ($entity) {
                $childrenToAdd[] = $entity;
                $identifiersToKeep[] = $childIdentifiers;
                return;
            }
        }

        // Is this a new child? We might not be able to add it...
            if (!$childEntity && !$field->canCreateNewChildren()) {
            throw new EntityNotFoundException(
                // The related resource does not exist.
                "The related " . $child->getResourceDefinition()->getEntityName() . " at " . ObjectHelper::class_basename($parent) . "->" . $field->getName() . " does not exist"
            );
        }

        $entity = $resourceTransformer->toEntity(
            $child,
            $factory,
            $context,
            $childEntity
        );

        if (!isset($childEntity)) {
            $childrenToAdd[] = $entity;
        } elseif ($field->canCreateNewChildren()) {
            $childrenToEdit[] = $entity;
        }
    }

    /**
     * @param Context $context
     * @param string $path
     * @param bool $validateNonProvidedFields
     * @throws PropertyValidationException
     * @throws RequirementValidationException
     * @throws ResourceException
     * @throws \CatLab\Requirements\Exceptions\ValidationException
     */
    public function validate(Context $context, string $path, $validateNonProvidedFields = true)
    {
        $messages = new MessageCollection();

        $field = $this->getField();
        if (!$field instanceof RelationshipField) {
            throw new ResourceException("RelationshipValue found without a RelationshipField.");
        }

        if ($field->canCreateNewChildren()) {
            /*
             * Now check the children
             */
            foreach ($this->getChildrenToProcess() as $child) {
                /** @var RESTResource $child */
                if ($child) {
                    try {
                        $child->validate($context, null, $this->appendToPath($path, $field), $validateNonProvidedFields);
                    } catch (ResourceValidationException $e) {
                        $messages->merge($e->getMessages());
                    }
                } else {
                    $this->getField()->validate(null, $this->appendToPath($path, $field), $validateNonProvidedFields);
                }
            }
        } elseif ($field->canLinkExistingEntities()) {
            /*
             *  We only need identifiers...
             */
            $identifiers = $field->getChildResource()->getFields()->getIdentifiers();

            foreach ($this->getChildrenToProcess() as $child) {
                if ($child) {
                    /** @var RESTResource $child */
                    foreach ($identifiers as $identifier) {
                        /** @var IdentifierField $identifier */
                        $prop = $child->getProperties()->getFromName($identifier->getName());
                        if (!$prop || $prop->getValue() === null) {
                            $identifier->setPath($this->appendToPath($path, $field));
                            $propertyException = RequirementValidationException::make($identifier, new Exists(), null);
                            $messages = new MessageCollection();
                            $messages->add($propertyException->getRequirement()->getErrorMessage($propertyException));
                            throw PropertyValidationException::make($identifier, $messages);
                        }
                    }
                } else {
                    try {
                        $this->getField()->validate(null, $this->appendToPath($path, $field), $validateNonProvidedFields);
                    } catch (ResourceValidationException $e) {
                        $messages->merge($e->getMessages());
                    }
                }
            }
        }

        if (count($messages) > 0) {
            throw PropertyValidationException::make($field, $messages);
        }
    }

    /**
     * @param $path
     * @param Field $field
     * @return string
     */
    private function appendToPath($path, Field $field)
    {
        $display = $field->getDisplayName();
        if ($field instanceof RelationshipField) {
            if ($field->getCardinality() === Cardinality::MANY) {
                $display .= '[]';
            }
        }

        if (!empty($path)) {
            return $path . '.' . $display;
        } else {
            return $display;
        }
    }
}
