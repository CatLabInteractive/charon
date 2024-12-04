<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Values\Base;

use CatLab\Charon\Exceptions\EntityNotFoundException;
use CatLab\Charon\Exceptions\LinkRelationshipContainsAttributesException;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Identifier;
use CatLab\Requirements\Collections\MessageCollection;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Exceptions\RequirementValidationException;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use CatLab\Requirements\Exists;
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
use CatLab\Requirements\Models\Message;
use CatLab\Requirements\Models\TranslatableMessage;

/**
 * Class RelationshipValue
 * @package CatLab\RESTResource\Models\Values\Base
 */
abstract class RelationshipValue extends Value
{
    private ?string $url = null;

    private ?\CatLab\Charon\Interfaces\Context $context = null;

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
     * @param Identifier[] $identifiers
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
    ): void;

    /**
     * @param ResourceTransformer $transformer
     * @param PropertyResolver $propertyResolver
     * @param $parent
     * @param Identifier $identifier
     * @param Context $context
     * @return mixed
     */
    abstract protected function getChildByIdentifiers(
        ResourceTransformer $transformer,
        PropertyResolver $propertyResolver,
        &$parent,
        Identifier $identifier,
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
    ): void {
        $children = $this->getChildrenToProcess();

        $childrenToAdd = [];
        $childrenToEdit = [];

        /**
         * Keep a list of all identifies we've touched, so we can remove those we haven't
         * @var Identifier[] $identifiersToKeep
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
     * @param Identifier[] $identifiersToKeep
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
    ): void {
        /** @var RelationshipField $field */
        $field = $this->getField();

        /** @var bool $isNew */
        $isNew = $child->isNew();

        $childEntity = null;

        if (!$isNew) {
            $childEntity = $this->getChildByIdentifiers(
                $resourceTransformer,
                $propertyResolver,
                $parent,
                $child->getIdentifier(),
                $context
            );

            if ($childEntity) {
                $identifiersToKeep[] = $child->getIdentifier();

                // if we can't edit the child from here, there is no points in going further.
                if (!$field->canCreateNewChildren($context)) {
                    return;
                }
            }
        }

        // Do we just link the resource? In this case we can't edit it right now.
        if (
            $field->canLinkExistingEntities($context) &&
            !$childEntity
        ) {
            $entity = $factory->resolveLinkedEntity(
                $parent,
                $child->getResourceDefinition()->getEntityClassName(),
                $child->getIdentifier(),
                $context
            );

            if ($entity) {
                $childrenToAdd[] = $entity;
                $identifiersToKeep[] = $child->getIdentifier();
                return;
            }
        }

        // Is this a new child? We might not be able to add it...
        if (!$childEntity && !$field->canCreateNewChildren($context)) {
            throw EntityNotFoundException::makeTranslatable(
                // The related resource does not exist.
                "The related %s at %s does not exist.",
                [
                    $child->getResourceDefinition()->getEntityName(),
                    ObjectHelper::class_basename($parent) . '->' . $field->getName()
                ]
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
        } elseif ($field->canCreateNewChildren($context)) {
            $childrenToEdit[] = $entity;
        }
    }

    /**
     * @param Context $context
     * @param CurrentPath $path
     * @param bool $validateNonProvidedFields
     * @throws PropertyValidationException
     * @throws RequirementValidationException
     * @throws ResourceException
     * @throws \CatLab\Requirements\Exceptions\ValidationException
     */
    public function validate(Context $context, CurrentPath $path, $validateNonProvidedFields = true): void
    {
        $messages = new MessageCollection();

        $field = $this->getField();
        if (!$field instanceof RelationshipField) {
            throw new ResourceException("RelationshipValue found without a RelationshipField.");
        }

        if ($field->canCreateNewChildren($context)) {
            /*
             * Now check the children
             */
            foreach ($this->getChildrenToProcess() as $child) {
                /** @var RESTResource $child */
                if ($child) {
                    // First check if this could be a 'linkable' request
                    try {
                        if ($field->canLinkExistingEntities($context)) {
                            $this->validateLinkableResource($child, $path);
                        }
                    } catch (PropertyValidationException $e) {
                        // If not, do a full validation.
                        try {
                            $child->validate(
                                $context,
                                null,
                                $this->appendToPath($path, $field),
                                $validateNonProvidedFields
                            );
                        } catch (ResourceValidationException $e) {
                            $messages->merge($e->getMessages());
                        }
                    }

                } else {
                    $this->getField()->validate(
                        null,
                        (string) $this->appendToPath($path, $field),
                        $validateNonProvidedFields
                    );
                }
            }
        } elseif ($field->canLinkExistingEntities($context)) {
            /*
             *  We only need identifiers...
             */
            foreach ($this->getChildrenToProcess() as $child) {
                if ($child) {
                    $this->validateLinkableResource($child, $path);
                } else {
                    try {
                        $this->getField()->validate(
                            null,
                            (string) $this->appendToPath($path, $field),
                            $validateNonProvidedFields
                        );
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
     * @param RESTResource $child
     * @param CurrentPath $path
     * @return bool
     * @throws PropertyValidationException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    protected function validateLinkableResource(RESTResource $child, CurrentPath $path)
    {
        $field = $this->getField();

        $identifiers = $field->getChildResource()->getFields()->getIdentifiers();

        /** @var RESTResource $child */
        foreach ($identifiers as $identifier) {
            /** @var IdentifierField $identifier */
            $prop = $child->getProperties()->getFromName($identifier->getName());
            if (!$prop || $prop->getValue() === null) {
                $identifier->setPath((string) $this->appendToPath($path, $field));
                $propertyException = RequirementValidationException::make($identifier, new Exists(), null);
                $messages = new MessageCollection();
                $messages->add($propertyException->getRequirement()->getErrorMessage($propertyException));
                throw PropertyValidationException::make($identifier, $messages);
            }
        }

        // But it can't have any other attributes!
        $fields = $field->getChildResource()->getFields();
        foreach ($fields as $field) {
            if (!($field instanceof IdentifierField)) {
                $prop = $child->getProperties()->getFromName($field->getName());
                if ($prop && $prop->getValue() !== null) {
                    $messages = new MessageCollection();
                    $message = new TranslatableMessage(
                        'Linkable resources may not contain any other attributes.',
                        [],
                        null,
                        $field->getDisplayName()
                    );

                    $messages->add($message);
                    throw LinkRelationshipContainsAttributesException::make($field, $messages);
                }
            }
        }

        return true;
    }

    /**
     * @param CurrentPath $path
     * @param Field $field
     * @return CurrentPath
     */
    private function appendToPath(CurrentPath $path, Field $field): \CatLab\Charon\Models\CurrentPath
    {
        return $path->clonePush($field);
    }
}
