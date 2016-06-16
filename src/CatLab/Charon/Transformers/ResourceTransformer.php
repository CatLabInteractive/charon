<?php

namespace CatLab\Charon\Transformers;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Base\Models\Database\WhereParameter;
use CatLab\Charon\Collections\ParentEntities;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DynamicContext;
use CatLab\Charon\Interfaces\PropertyResolver;
use CatLab\Charon\Interfaces\PropertySetter;
use CatLab\Charon\Interfaces\RESTResource as ResourceContract;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\EntityFactory as EntityFactoryContract;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\InvalidContextAction;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Exceptions\InvalidEntityException;
use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Interfaces\ResourceTransformer as ResourceTransformerContract;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class ResourceTransformer
 * @package CatLab\RESTResource\Transformers
 */
class ResourceTransformer implements ResourceTransformerContract
{
    /**
     * @var PropertyResolver
     */
    private $propertyResolver;

    /**
     * @var PropertySetter
     */
    private $propertySetter;

    /**
     * @var string[]
     */
    private $currentPath;

    /**
     * @var mixed[]
     */
    private $parents;

    /**
     * ResourceTransformer constructor.
     * @param PropertyResolver $propertyResolver
     * @param PropertySetter $propertySetter
     */
    public function __construct(
        PropertyResolver $propertyResolver = null,
        PropertySetter $propertySetter = null
    ) {
        if (!isset($propertyResolver)) {
            $propertyResolver = new \CatLab\Charon\Resolvers\PropertyResolver();
        }

        if (!isset($propertySetter)) {
            $propertySetter = new \CatLab\Charon\Resolvers\PropertySetter();
        }

        $this->propertyResolver = $propertyResolver;
        $this->propertySetter = $propertySetter;
        $this->parents = new ParentEntities();
    }

    /**
     * @return mixed
     */
    public function getParentEntity()
    {
        return $this->parents->getParent();
    }

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param mixed $entities
     * @param ContextContract $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceCollection
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     */
    public function toResources(
        $resourceDefinition,
        $entities,
        ContextContract $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : ResourceCollection {
        if (!ArrayHelper::isIterable($entities)) {
            throw new InvalidEntityException(__CLASS__ . '::toResources expects an iterable object of entities.');
        }

        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);

        $out = new ResourceCollection();

        foreach ($entities as $entity) {
            $out->add($this->toResource($resourceDefinition, $entity, $context, $parent, $parentEntity));
        }

        $context->getProcessors()->processCollection(
            $this,
            $out,
            $resourceDefinition,
            $context,
            $parent,
            $parentEntity
        );

        return $out;
    }

    /**
     * @param ResourceDefinition|string $resourceDefinition
     * @param mixed $entity
     * @param ContextContract $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceContract
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     */
    public function toResource(
        $resourceDefinition,
        $entity,
        ContextContract $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : ResourceContract {
        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);
        $this->checkEntityType($resourceDefinition, $entity);

        if (!Action::isReadContext($context->getAction())) {
            throw InvalidContextAction::create('Readable', $context->getAction());
        }

        if ($resourceDefinition instanceof DynamicContext) {
            $resourceDefinition->transformContext($context, $entity);
        }

        if ($entity instanceof DynamicContext) {
            $entity->transformContext($context, $entity);
        }

        $resource = new RESTResource($resourceDefinition);
        $fields = $resourceDefinition->getFields();

        $this->parents->push($entity);

        foreach ($fields as $field) {
            $this->currentPath[] = $field->getDisplayName();
            $visible = $this->shouldInclude($field, $context);
            if ($visible || $field->isSortable()) {
                if ($field instanceof RelationshipField) {
                    if ($this->shouldExpand($field, $context)) {
                        $this->expandRelationship($field, $entity, $resource, $context, $visible);
                    } else {
                        $this->linkRelationship($field, $entity, $resource, $context, $visible);
                    }
                } else {
                    $resource->setProperty(
                        $field,
                        $this->propertyResolver->resolveProperty($this, $entity, $field, $context),
                        $visible
                    );
                }
            }
            array_pop($this->currentPath);
        }

        $context->getProcessors()->processResource(
            $this,
            $resource,
            $resourceDefinition,
            $context,
            $parent,
            $parentEntity
        );

        $this->parents->pop();
        return $resource;
    }

    /**
     * @param ResourceContract $resource
     * @param $resourceDefinition
     * @param EntityFactoryContract $factory
     * @param mixed|null $entity
     * @param ContextContract $context
     * @return mixed $entity
     */
    public function toEntity(
        ResourceContract $resource,
        $resourceDefinition,
        EntityFactoryContract $factory,
        Context $context,
        $entity = null
    ) {
        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);

        if ($entity === null) {
            $entity = $factory->createEntity($resourceDefinition->getEntityClassName(), $context);
        }

        $this->parents->push($entity);

        $values = $resource->getProperties()->getValues();
        foreach ($values as $property) {
            $property->toEntity($entity, $this, $this->propertyResolver, $this->propertySetter, $factory, $context);
        }

        $this->parents->pop();

        return $entity;
    }

    /**
     * Create a resource from a data array
     * @param $resourceDefinition
     * @param array $body
     * @param ContextContract $context
     * @return ResourceContract
     * @throws InvalidPropertyException
     * @throws InvalidContextAction
     */
    public function fromArray($resourceDefinition, array $body, ContextContract $context) : ResourceContract
    {
        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);
        if (!Action::isWriteContext($context->getAction())) {
            throw InvalidContextAction::create('Writeable', $context->getAction());
        }

        $resource = new RESTResource($resourceDefinition);
        $fields = $resourceDefinition->getFields();

        foreach ($fields as $field) {
            $this->currentPath[] = $field->getDisplayName();

            if ($this->isWritable($field, $context)) {
                if ($field instanceof RelationshipField) {
                    $this->relationshipFromArray($field, $body, $resource, $context);
                } else {
                    $value = $this->propertyResolver->resolvePropertyInput(
                        $this,
                        $body,
                        $field,
                        $context
                    );

                    $resource->setProperty($field, $value, true);
                }
            }
            array_pop($this->currentPath);
        }

        return $resource;
    }

    /**
     * @param $resourceDefinition
     * @param $content
     * @param EntityFactoryContract $factory
     * @param ContextContract $context
     * @return array
     * @throws InvalidContextAction
     */
    public function fromIdentifiers(
        $resourceDefinition,
        $content,
        EntityFactoryContract $factory,
        ContextContract $context
    ) {
        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);
        if (!Action::isWriteContext($context->getAction())) {
            throw InvalidContextAction::create('Writeable', $context->getAction());
        }

        $out = [];
        if (isset($content[self::RELATIONSHIP_ITEMS])) {
            // This is a list of items
            foreach ($content[self::RELATIONSHIP_ITEMS] as $item) {
                $out[] = $this->fromIdentifier($resourceDefinition, $item, $factory, $context);
            }
        } else {
            $out[] = $this->fromIdentifier($resourceDefinition, $content, $factory, $context);
        }
        return $out;
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param $content
     * @param EntityFactoryContract $factory
     * @param ContextContract $context
     * @return mixed
     */
    private function fromIdentifier(
        ResourceDefinition $resourceDefinition,
        $content,
        EntityFactoryContract $factory,
        ContextContract $context
    ) {
        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);
        return $factory->resolveFromIdentifier($resourceDefinition->getEntityClassName(), $content, $context);
    }

    /**
     * @param $request
     * @param $resourceDefinition
     * @param ContextContract $context
     * @param int $records
     * @return SelectQueryParameters
     */
    public function getFilters($request, $resourceDefinition, Context $context, int $records = 10)
    {
        $definition = ResourceDefinitionLibrary::make($resourceDefinition);
        
        $queryBuilder = new SelectQueryParameters();

        // Now check for query parameters
        foreach ($definition->getFields() as $field) {
            if (
                $field instanceof ResourceField &&
                $field->isFilterable()
            ) {
                $parameter = $this->propertyResolver->getParameterFromRequest($request, $field->getDisplayName());
                if ($parameter) {
                    $queryBuilder->where(new WhereParameter($field->getName(), Operator::EQ, $parameter));
                }
            }
        }
        
        // Processors
        $context->getProcessors()->processFilters($this, $queryBuilder, $request, $definition, $context, $records);
        
        return $queryBuilder;
    }

    /**
     * @param RelationshipField $field
     * @param mixed $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     * @throws InvalidPropertyException
     */
    private function expandRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        Context $context,
        $visible = true
    ) {
        switch ($field->getCardinality()) {
            case Cardinality::MANY:
                $children = $this->propertyResolver->resolveManyRelationship(
                    $this,
                    $entity,
                    $resource->touchChildrenProperty($field),
                    $context
                );

                $resource->setChildrenProperty($field, $children, $visible);
                break;

            case Cardinality::ONE:
                $child = $this->propertyResolver->resolveOneRelationship(
                    $this,
                    $entity,
                    $resource->touchChildProperty($field),
                    $context
                );

                if ($child) {
                    $resource->setChildProperty($field, $child, $visible);
                } else {
                    $resource->clearProperty($field);
                }
                break;

            default:
                throw new InvalidPropertyException("Relationship has invalid type.");
        }
    }

    /**
     * @param RelationshipField $field
     * @param &$body
     * @param RESTResource $resource
     * @param ContextContract $context
     * @throws InvalidPropertyException
     */
    private function relationshipFromArray(RelationshipField $field, &$body, RESTResource $resource, Context $context)
    {
        switch ($field->getCardinality()) {
            case Cardinality::MANY:
                $children = $this->propertyResolver->resolveManyRelationshipInput(
                    $this,
                    $body,
                    $field,
                    $context
                );

                $resource->setChildrenProperty($field, $children, true);
                break;

            case Cardinality::ONE:
                $child = $this->propertyResolver->resolveOneRelationshipInput($this, $body, $field, $context);
                if ($child) {
                    $resource->setChildProperty($field, $child, true);
                } else {
                    $resource->setChildProperty($field, null, true);
                }
                break;

            default:
                throw new InvalidPropertyException("Relationship has invalid type.");
        }
    }

    /**
     * @param RelationshipField $field
     * @param $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     * @return array
     * @throws InvalidPropertyException
     */
    private function linkRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        Context $context,
        $visible
    ) {
        $resource->setLink(
            $field,
            $this->propertyResolver->resolvePathParameters($this, $entity, $field->getUrl(), $context),
            $visible
        );
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param $entity
     * @throws InvalidEntityException
     */
    private function checkEntityType(ResourceDefinition $resourceDefinition, $entity)
    {
        $entityClassName = $resourceDefinition->getEntityClassName();
        if (! ($entity instanceof $entityClassName)) {

            if (is_object($entity)) {
                $providedType = get_class($entity);
            } else {
                $providedType = gettype($entity);
            }

            throw new InvalidEntityException(
                "ResourceTransformer expects $entityClassName, " . $providedType . " given."
            );
        }
    }

    /**
     * @param Field $field
     * @param Context $context
     * @return bool
     */
    private function shouldInclude(Field $field, Context $context)
    {
        return $field->shouldInclude($context, $this->currentPath);
    }

    /**
     * @param Field $field
     * @param ContextContract $context
     * @return bool
     */
    private function isWritable(Field $field, Context $context)
    {
        return $field->shouldInclude($context, $this->currentPath);
    }

    /**
     * @param RelationshipField $field
     * @param ContextContract $context
     * @return bool
     */
    private function shouldExpand(RelationshipField $field, Context $context)
    {
        return $field->shouldExpand($context, $this->currentPath);
    }

    /**
     * @return PropertyResolver
     */
    public function getPropertyResolver() : PropertyResolver
    {
        return $this->propertyResolver;
    }

    /**
     * @return PropertySetter
     */
    public function getPropertySetter() : PropertySetter
    {
        return $this->propertySetter;
    }
}