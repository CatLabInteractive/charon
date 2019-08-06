<?php

namespace CatLab\Charon\Models\Values\Base;

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

/**
 * Class RelationshipValue
 * @package CatLab\RESTResource\Models\Values\Base
 */
abstract class RelationshipValue extends Value
{
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
     * @return RelationshipField
     */
    public function getField()
    {
        return parent::getField();
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
                return;
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
     * @throws InvalidPropertyException
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
            throw new InvalidPropertyException(
                "Only existing items can be linked to " . get_class($parent) . "->" . $field->getName()
            );
        }

        $entity = $resourceTransformer->toEntity(
            $child,
            $child->getResourceDefinition(),
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
     * @throws PropertyValidationException
     * @throws ResourceException
     * @throws RequirementValidationException
     */
    public function validate(Context $context, string $path)
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
                        $child->validate($context, null, $this->appendToPath($path, $field));
                    } catch (ResourceValidationException $e) {
                        $messages->merge($e->getMessages());
                    }
                } else {
                    $this->getField()->validate(null, $this->appendToPath($path, $field));
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
                        $prop = $child->getProperties()->getProperty($identifier);
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
                        $this->getField()->validate(null, $this->appendToPath($path, $field));
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