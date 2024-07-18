<?php

declare(strict_types=1);

namespace CatLab\Charon;

use CatLab\Charon\Collections\FilterCollection;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Exceptions\InvalidResourceDefinition;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\DynamicContext as DynamicContextContract;
use CatLab\Charon\Interfaces\IdentifierCollection as IdentifierCollectionContract;
use CatLab\Charon\Interfaces\PropertyResolver as PropertyResolverContract;
use CatLab\Charon\Interfaces\PropertySetter as PropertySetterContract;
use CatLab\Charon\Interfaces\QueryAdapter as QueryAdapterContract;
use CatLab\Charon\Interfaces\RequestResolver as RequestResolverContract;
use CatLab\Charon\Interfaces\ResourceCollection as ResourceCollectionContract;
use CatLab\Charon\Interfaces\ResourceDefinitionFactory;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryContract;
use CatLab\Charon\Interfaces\RESTResource as ResourceContract;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\EntityFactory as EntityFactoryContract;
use CatLab\Charon\Interfaces\ResourceTransformer as ResourceTransformerContract;

use CatLab\Base\Enum\Operator;
use CatLab\Base\Helpers\ArrayHelper;

use CatLab\Charon\Collections\InputParserCollection;
use CatLab\Charon\Collections\ParentEntityCollection;

use CatLab\Charon\Exceptions\NoInputDataFound;
use CatLab\Charon\Exceptions\ValueUndefined;
use CatLab\Charon\Exceptions\IterableExpected;
use CatLab\Charon\Exceptions\InvalidContextAction;
use CatLab\Charon\Exceptions\InvalidEntityException;
use CatLab\Charon\Exceptions\InvalidPropertyException;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;

use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Filter;
use CatLab\Charon\Models\FilterResults;
use CatLab\Charon\Models\Identifier;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\RESTResource;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\StaticResourceDefinitionFactory;
use CatLab\Charon\Models\Values\Base\RelationshipValue;
use CatLab\Charon\SimpleResolvers\SimplePropertyResolver;
use CatLab\Charon\SimpleResolvers\SimplePropertySetter;
use CatLab\Charon\SimpleResolvers\SimpleQueryAdapter;
use CatLab\Charon\SimpleResolvers\SimpleRequestResolver;
use CatLab\Charon\SimpleResolvers\SimpleResourceFactory;

/**
 * Class ResourceTransformer
 * @package CatLab\RESTResource\Transformers
 */
abstract class ResourceTransformer implements ResourceTransformerContract
{
    protected \CatLab\Charon\Interfaces\PropertyResolver $propertyResolver;

    protected \CatLab\Charon\Interfaces\PropertySetter $propertySetter;

    protected \CatLab\Charon\Interfaces\RequestResolver $requestResolver;

    protected \CatLab\Charon\Interfaces\QueryAdapter $queryAdapter;

    protected \CatLab\Charon\Interfaces\ResourceFactory $resourceFactory;

    protected \CatLab\Charon\Models\CurrentPath $currentPath;

    /**
     * @var mixed[]
     */
    protected \CatLab\Charon\Collections\ParentEntityCollection $parents;

    /**
     * @var int
     */
    protected $maxDepth = 50;

    protected \CatLab\Charon\Collections\InputParserCollection $inputParsers;

    /**
     * ResourceTransformer constructor.
     * @param PropertyResolverContract $propertyResolver
     * @param PropertySetterContract $propertySetter
     * @param RequestResolverContract $requestResolver
     * @param QueryAdapterContract $queryAdapter
     * @param ResourceFactoryContract $resourceFactory
     */
    public function __construct(
        PropertyResolverContract $propertyResolver = null,
        PropertySetterContract $propertySetter = null,
        RequestResolverContract $requestResolver = null,
        QueryAdapterContract $queryAdapter = null,
        ResourceFactoryContract $resourceFactory = null
    ) {
        $this->propertyResolver = $propertyResolver ?? new SimplePropertyResolver();
        $this->propertySetter = $propertySetter ?? new SimplePropertySetter();
        $this->resourceFactory = $resourceFactory ?? new SimpleResourceFactory();
        $this->requestResolver = $requestResolver ?? new SimpleRequestResolver();
        $this->queryAdapter = $queryAdapter ?? new SimpleQueryAdapter();

        $this->currentPath = new CurrentPath();
        $this->parents = new ParentEntityCollection();
        $this->inputParsers = new InputParserCollection();
    }

    /**
     * @return mixed
     */
    public function getParentEntity()
    {
        return $this->parents->getParent();
    }

    /**
     * @param ResourceDefinitionContract|string $resourceDefinition
     * @param mixed $entities
     * @param ContextContract $context
     * @param FilterResults|null $filterResults
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceCollectionContract
     * @throws Exceptions\InvalidTransformer
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     * @throws IterableExpected
     * @throws Exceptions\InvalidResourceDefinition
     */
    public function toResources(
        $resourceDefinition,
        $entities,
        ContextContract $context,
        FilterResults $filterResults = null,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : \CatLab\Charon\Interfaces\ResourceCollection {

        if (!ArrayHelper::isIterable($entities)) {
            throw InvalidEntityException::makeTranslatable('%s expects an iterable object of entities at %s.', [
                self::class . '::toResources',
                $this->currentPath
            ]);
        }

        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);

        $out = $this->getResourceFactory()->createResourceCollection();

        $index = 0;
        foreach ($entities as $entity) {
            $entityResDef = $resourceDefinitionFactory->fromEntity($entity);
            $resource = $this->toResource($entityResDef, $entity, $context, $parent, $parentEntity);

            $out->add($resource);
            ++$index;
        }

        $context->getProcessors()->processCollection(
            $this,
            $out,
            $resourceDefinitionFactory,
            $context,
            $filterResults,
            $parent,
            $parentEntity
        );

        return $out;
    }

    /**
     * Given a ResourceDefinition and the entity it belongs to, return
     * the (processed) ResourceDefinition. Useful in case ResourceDefinitionFactories are used.
     * @param $resourceDefinition
     * @param $entity
     * @return ResourceDefinitionContract
     * @throws InvalidResourceDefinition
     */
    public function getResourceDefinition($resourceDefinition, $entity)
    {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        return $resourceDefinitionFactory->fromEntity($entity);
    }

    /**
     * @param ResourceDefinitionContract|string $resourceDefinition
     * @param mixed $entity
     * @param ContextContract $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @return ResourceContract
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     * @throws IterableExpected
     * @throws Exceptions\InvalidTransformer
     * @throws Exceptions\InvalidResourceDefinition
     */
    public function toResource(
        $resourceDefinition,
        $entity,
        ContextContract $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) : ResourceContract {
        $resourceDefinition = $this->getResourceDefinition($resourceDefinition, $entity);

        $this->checkEntityType($resourceDefinition, $entity);

        if (!Action::isReadContext($context->getAction())) {
            throw InvalidContextAction::expectedReadable($context->getAction());
        }

        // Dynamic context required?
        if (
            $resourceDefinition instanceof DynamicContextContract ||
            $entity instanceof DynamicContextContract
        ) {
            // In case of dynamic context we must start from a fork of the context
            $context = $context->fork();

            if ($resourceDefinition instanceof DynamicContextContract) {
                $resourceDefinition->transformContext($context, $entity);
            }

            if ($entity instanceof DynamicContextContract) {
                $entity->transformContext($context, $entity);
            }
        }

        $resource = new RESTResource($resourceDefinition);
        $resource->setSource($entity);

        $fields = $resourceDefinition->getFields();

        $this->parents->push($entity);

        /** @var Field $field */
        foreach ($fields as $field) {
            $this->currentPath->push($field);

            if ($this->shouldInclude($field, $context)) {

                $visible = $field->shouldInclude($context, $this->currentPath);
                if ($field instanceof RelationshipField) {
                    if ($this->shouldExpand($field, $context)) {
                        $this->expandRelationship($field, $entity, $resource, $context, $visible);
                    } else {
                        $this->linkRelationship($field, $entity, $resource, $context, $visible);
                    }
                } elseif ($field instanceof ResourceField) {
                    $value = $this->getPropertyResolver()->resolveProperty($this, $entity, $field, $context);

                    if ($field->isArray()) {
                        // Null values = empty arrays.
                        if ($value === null) {
                            $value = [];
                        }

                        if (!ArrayHelper::isIterable($value)) {
                            throw IterableExpected::make($field, $value);
                        }

                        // no data? No processing.
                        if (count($value) === 0) {
                            // Is this field supposed to be a map? In that case use a stdClass
                            if ($field->isMap()) {
                                $value = new \stdClass();
                            }
                        } else {

                            // Translate to regular array (otherwise we might get in trouble)
                            $transformedValue = [];
                            $transformer = $field->getTransformer();

                            if ($transformer) {
                                foreach ($value as $k => $v) {
                                    $transformedValue[$k] = $transformer->toResourceValue($v, $context);
                                }
                            } else {
                                foreach ($value as $k => $v) {
                                    $transformedValue[$k] = $v;
                                }
                            }

                            $value = $transformedValue;

                        }
                    } elseif ($transformer = $field->getTransformer()) {
                        $value = $transformer->toResourceValue($value, $context);
                    }

                    $resource->setProperty(
                        $field,
                        $value,
                        $visible
                    );
                } else {
                    throw new \InvalidArgumentException("Unexpected field type found: " . get_class($field));
                }
            }

            $this->currentPath->pop();
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
     * @param EntityFactoryContract $factory
     * @param mixed|null $entity
     * @param ContextContract $context
     * @return mixed $entity
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     * @throws InvalidResourceDefinition
     */
    public function toEntity(
        ResourceContract $resource,
        EntityFactoryContract $factory,
        ContextContract $context,
        $entity = null
    ) {
        /*
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $resourceDefinition = $resourceDefinitionFactory->fromResource($resource);
        */

        $resourceDefinition = $resource->getResourceDefinition();

        if ($entity === null) {
            $entity = $factory->createEntity($resourceDefinition->getEntityClassName(), $context);
        }

        $this->parents->push($entity);

        $values = $resource->getProperties()->getValues();
        foreach ($values as $property) {
            $property->toEntity(
                $entity,
                $this,
                $this->getPropertyResolver(),
                $this->getPropertySetter(),
                $factory,
                $context
            );
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
     * @throws InvalidResourceDefinition
     */
    public function fromArray(
        $resourceDefinition,
        array $body,
        ContextContract $context
    ) : ResourceContract {

        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $resourceDefinition = $resourceDefinitionFactory->fromRawInput($body);

        if (!Action::isWriteContext($context->getAction())) {
            throw InvalidContextAction::expectedWriteable($context->getAction());
        }

        $resource = new RESTResource($resourceDefinition);
        $resource->setSource($body);

        $fields = $resourceDefinition->getFields();

        foreach ($fields as $field) {
            $this->currentPath->push($field);

            if ($this->isWritable($field, $context)) {
                if ($field instanceof RelationshipField) {
                    $this->relationshipFromArray($field, $body, $resource, $context);
                } else {
                    try {
                        $value = $this->getPropertyResolver()->resolvePropertyInput(
                            $this,
                            $body,
                            $field,
                            $context
                        );

                        $resource->setProperty($field, $value, true);
                    } catch (ValueUndefined $e) {
                        // Don't worry, be happy.
                    }
                }
            }

            $this->currentPath->pop();
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
     * @throws InvalidResourceDefinition
     */
    public function entitiesFromIdentifiers(
        $resourceDefinition,
        array $content,
        EntityFactoryContract $factory,
        ContextContract $context
    ) {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $resourceDefinition = $resourceDefinitionFactory->fromIdentifiers($content);

        if (!Action::isWriteContext($context->getAction())) {
            throw InvalidContextAction::expectedWriteable($context->getAction());
        }

        $out = [];
        if ($content instanceof IdentifierCollectionContract) {
            // Collection of Identifier objects
            foreach ($content as $v) {
                $entity = $this->fromIdentifier($resourceDefinition, $v, $factory, $context);
                if ($entity) {
                    $out[] = $entity;
                }
            }
        } elseif (isset($content[self::RELATIONSHIP_ITEMS])) {
            // This is a list of items
            foreach ($content[self::RELATIONSHIP_ITEMS] as $item) {
                $entity = $this->fromIdentifier($resourceDefinition, $item, $factory, $context);
                if ($entity) {
                    $out[] = $entity;
                }
            }
        } else {
            $entity = $this->fromIdentifier($resourceDefinition, $content, $factory, $context);
            if ($entity) {
                $out[] = $entity;
            }
        }

        return $out;
    }

    /**
     * @param ResourceDefinitionContract $resourceDefinition
     * @param $identifier
     * @param EntityFactoryContract $factory
     * @param ContextContract $context
     * @return mixed
     * @throws InvalidResourceDefinition
     */
    private function fromIdentifier(
        ResourceDefinitionContract $resourceDefinition,
        $identifier,
        EntityFactoryContract $factory,
        ContextContract $context
    ) {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $resourceDefinition = $resourceDefinitionFactory->fromIdentifiers([ $identifier ]);

        if (! ($identifier instanceof Identifier)) {
            $identifier = Identifier::fromArray($resourceDefinition, $identifier);
        }

        return $factory->resolveFromIdentifier(
            $resourceDefinition->getEntityClassName(),
            $identifier,
            $context
        );
    }

    /**
     * Given a querybuilder or a list of items, process eager loading for each relationship that should be visible.
     * This method should be called before calling toEntities, and is also called for each relationship that needs
     * to be loaded.
     * @param $entities
     * @param $resourceDefinition
     * @param ContextContract $context
     * @throws Exceptions\InvalidResourceDefinition
     */
    public function processEagerLoading($entities, $resourceDefinition = null, ContextContract $context = null): void
    {
        if (!$resourceDefinition) {
            return;
        }

        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $definition = $resourceDefinitionFactory->getDefault();

        // Now check for query parameters
        foreach ($definition->getFields() as $field) {

            $this->currentPath->push($field);

            if (
                $field instanceof RelationshipField &&
                $this->shouldInclude($field, $context) &&
                $this->shouldExpand($field, $context)
            ) {
                $this->getQueryAdapter()->eagerLoadRelationship($this, $entities, $field, $context);
            }

            $this->currentPath->pop();
        }
    }

    /**
     * @param $request
     * @param $resourceDefinition
     * @param ContextContract $context
     * @return FilterCollection
     * @throws InvalidResourceDefinition
     */
    public function getFilters(
        $request,
        $resourceDefinition,
        ContextContract $context
    ) {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);
        $definition = $resourceDefinitionFactory->getDefault();

        $filterCollection = new FilterCollection($definition);

        // First process all filtersable and searchable fields.
        foreach ($definition->getFields() as $field) {
            if ($field instanceof ResourceField) {
                // Filterable fields
                if (
                    $field->isFilterable() &&
                    $this->getRequestResolver()->hasFilter($request, $field, Operator::EQ)
                ) {

                    $value = $this->getRequestResolver()->getFilter($request, $field);
                    $filterCollection->add(new Filter($field, Operator::EQ, $value));

                } elseif (
                    $field->isSearchable() &&
                    $this->getRequestResolver()->hasFilter($request, $field, Operator::SEARCH)
                ) {
                    $value = $this->getRequestResolver()->getFilter($request, $field, Operator::SEARCH);
                    $filterCollection->add(new Filter($field, Operator::SEARCH, $value));
                }
            }
        }

        return $filterCollection;
    }

    /**
     * Apply any filterable/searchable fields
     * @param $request
     * @param FilterCollection $filters
     * @param ContextContract $context
     * @param $queryBuilder
     * @return FilterResults
     * @throws InvalidResourceDefinition
     */
    public function applyFilters(
        $request,
        FilterCollection $filters,
        ContextContract $context,
        $queryBuilder
    ) {
        $filterResults = new FilterResults();
        $filterResults->setQueryBuilder($queryBuilder);

        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($filters->getResourceDefinition());

        foreach ($filters as $filter) {
            /** @var Filter $filter */
            $this->getQueryAdapter()->applyPropertyFilter(
                $this,
                $filters->getResourceDefinition(),
                $context,
                $filter->getField(),
                $queryBuilder,
                $filter->getValue(),
                $filter->getOperator()
            );
        }

        // Now go through all processors and apply any filters or parameters they might want to set.
        $context->getProcessors()->processFilters(
            $this,
            $queryBuilder,
            $request,
            $resourceDefinitionFactory->getDefault(),
            $context,
            $filterResults
        );

        return $filterResults;
    }

    /**
     * @param $resourceDefinition
     * @return ResourceDefinitionFactory
     */
    public function getResourceDefinitionFactory($resourceDefinition)
    {
        return StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($resourceDefinition);
    }

    /**
     * @param RelationshipField $field
     * @param mixed $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     * @throws Exceptions\InvalidTransformer
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     * @throws IterableExpected
     * @throws InvalidResourceDefinition
     */
    private function expandRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        ContextContract $context,
        $visible = true
    ): void {
        if (count($this->parents) > $this->maxDepth) {
            $this->linkRelationship($field, $entity, $resource, $context, $visible);
            return;
        }

        switch ($field->getCardinality()) {
            case Cardinality::MANY:
                $this->expandManyRelationship($field, $entity, $resource, $context, $visible);
                break;

            case Cardinality::ONE:
                $this->expandOneRelationship($field, $entity, $resource, $context, $visible);
                break;

            default:
                throw InvalidPropertyException::makeTranslatable('Relationship has invalid type.');
        }
    }

    /**
     * @param RelationshipField $field
     * @param $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     * @throws Exceptions\InvalidTransformer
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     * @throws IterableExpected
     * @throws InvalidResourceDefinition
     */
    private function expandManyRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        ContextContract $context,
        $visible = true
    ): void {
        $url = $this->getPropertyResolver()->resolvePathParameters($this, $entity, $field->getUrl(), $context);
        $childrenValue = $resource->touchChildrenProperty($field);

        $childAction = $field->getExpandContext($context, $this->currentPath);
        $childContext = $context->getChildContext($field, $childAction);

        $childrenQueryBuilder = $this->getPropertyResolver()
            ->resolveManyRelationship($this, $entity, $field, $childContext);

        if (!$childrenQueryBuilder) {
            $resource->setChildrenProperty($childContext, $field, $url, new ResourceCollection(), $visible);
            return;
        }

        $childResourceFactory = $field->getChildResourceDefinitionFactory();
        $childResource = $field->getChildResourceDefinition();

        // Process eager loading
        // we actually don't want do eager loading as the eager loading should happen on the root entity
        //$this->processEagerLoading($childrenQueryBuilder, $childResourceFactory, $childContext);

        // fetch the records
        $children = $this->getQueryAdapter()
            ->getRecords(
                $this,
                $childResource,
                $context,
                $childrenQueryBuilder
            );

        // transform to resources
        $resources = $this->toResources(
            $childResourceFactory,
            $children,
            $childContext,
            null,
            $childrenValue,
            $entity
        );

        $resource->setChildrenProperty($childContext, $field, $url, $resources, $visible);
    }

    /**
     * @param RelationshipField $field
     * @param $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     * @throws Exceptions\InvalidTransformer
     * @throws InvalidContextAction
     * @throws InvalidEntityException
     * @throws InvalidPropertyException
     * @throws IterableExpected
     * @throws InvalidResourceDefinition
     */
    private function expandOneRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        ContextContract $context,
        $visible = true
    ): void {
        $url = $this->getPropertyResolver()
            ->resolvePathParameters($this, $entity, $field->getUrl(), $context);

        $childAction = $field->getExpandContext($context, $this->currentPath);
        $childContext = $context->getChildContext($field, $childAction);

        $child = $this->getPropertyResolver()
            ->resolveOneRelationship($this, $entity, $field, $childContext);

        $childValue = $resource->touchChildProperty($field);

        if ($child) {
            $childResource = $this->toResource(
                $field->getChildResourceDefinitionFactory(),
                $child,
                $childContext,
                $childValue,
                $entity
            );

            $resource->setChildProperty($childContext, $field, $url, $childResource, $visible);
        } else {
            $resource->clearProperty($field, $url);
        }
    }

    /**
     * @param RelationshipField $field
     * @param &$body
     * @param RESTResource $resource
     * @param ContextContract $context
     * @throws InvalidPropertyException
     */
    private function relationshipFromArray(RelationshipField $field, &$body, RESTResource $resource, ContextContract $context): void
    {
        // If no data is provided, don't set the property.
        if (!$this->getPropertyResolver()->hasRelationshipInput($this, $body, $field, $context)) {
            return;
        }

        switch ($field->getCardinality()) {
            case Cardinality::MANY:
                $children = $this->getPropertyResolver()->resolveManyRelationshipInput(
                    $this,
                    $body,
                    $field,
                    $context
                );

                $resource->setChildrenProperty($context, $field, null, $children, true);
                break;

            case Cardinality::ONE:
                $child = $this->getPropertyResolver()->resolveOneRelationshipInput($this, $body, $field, $context);
                if ($child) {
                    $resource->setChildProperty($context, $field, null, $child, true);
                } else {
                    $resource->setChildProperty($context, $field, null, null, true);
                }

                break;

            default:
                throw InvalidPropertyException::makeTranslatable('Relationship has invalid type.');
        }
    }

    /**
     * @param RelationshipField $field
     * @param $entity
     * @param RESTResource $resource
     * @param ContextContract $context
     * @param bool $visible
     */
    private function linkRelationship(
        RelationshipField $field,
        $entity,
        RESTResource $resource,
        ContextContract $context,
        $visible
    ): void {
        $url = $this->getPropertyResolver()->resolvePathParameters($this, $entity, $field->getUrl(), $context);
        $resource->setLink($context, $field, $url, $visible);
    }

    /**
     * @param ResourceDefinitionContract $resourceDefinition
     * @param $entity
     * @throws InvalidEntityException
     */
    private function checkEntityType(ResourceDefinitionContract $resourceDefinition, $entity): void
    {
        $entityClassName = $resourceDefinition->getEntityClassName();

        if ($entityClassName === null) {
            // Null given? Ok!
            return;
        }

        if (! ($entity instanceof $entityClassName)) {

            $providedType = is_object($entity) ? get_class($entity) : gettype($entity);

            throw InvalidEntityException::makeTranslatable(
                'ResourceTransformer expects %s, %s given.',
                [
                    $entityClassName,
                    $providedType
                ]
            );
        }
    }

    /**
     * @param Field $field
     * @param ContextContract $context
     * @return bool
     */
    private function shouldInclude(Field $field, ContextContract $context): bool
    {
        if ($field->shouldInclude($context, $this->currentPath)) {
            return true;
        }

        return $field->isRequiredForSorting();
    }

    /**
     * @param Field $field
     * @param ContextContract $context
     * @return bool
     */
    private function isWritable(Field $field, ContextContract $context): bool
    {
        if ($field instanceof IdentifierField) {
            return true;
        }

        return $field->isWriteable($context, $this->currentPath);
    }

    /**
     * @param RelationshipField $field
     * @param ContextContract $context
     * @return bool
     */
    private function shouldExpand(RelationshipField $field, ContextContract $context): bool
    {
        return $field->shouldExpand($context, $this->currentPath);
    }

    /**
     * @return PropertyResolverContract
     */
    public function getPropertyResolver() : PropertyResolverContract
    {
        return $this->propertyResolver;
    }

    /**
     * @return PropertySetterContract
     */
    public function getPropertySetter() : PropertySetterContract
    {
        return $this->propertySetter;
    }

    /**
     * @return RequestResolverContract
     */
    public function getRequestResolver(): RequestResolverContract
    {
        return $this->requestResolver;
    }

    /**
     * @return QueryAdapterContract
     */
    public function getQueryAdapter(): QueryAdapterContract
    {
        return $this->queryAdapter;
    }

    /**
     * @return ResourceFactoryContract
     */
    public function getResourceFactory(): ResourceFactoryContract
    {
        return $this->resourceFactory;
    }

    /**
     * @param Field $field
     * @return string
     */
    public function getQualifiedName(Field $field) : string
    {
        return $this->getQueryAdapter()->getQualifiedName($field);
    }

    /**
     * Create resources from whatever is in the inputs defined from the input parsers.
     * @param $resourceDefinition
     * @param ContextContract $context
     * @param null $request
     * @return ResourceCollectionContract
     * @throws NoInputDataFound
     * @throws InvalidResourceDefinition
     */
    public function fromInput(
        $resourceDefinition,
        ContextContract $context,
        $request = null
    ): ResourceCollectionContract
    {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);

        $resources = $context->getInputParser()->getResources($this, $resourceDefinitionFactory, $context, $request);

        if (!$resources) {
            throw NoInputDataFound::make();
        }

        return $resources;
    }

    /**
     * Create resource identifiers from whatever is in the inputs defined from the input parsers
     * @param $resourceDefinition
     * @param ContextContract $context
     * @param null $request
     * @return IdentifierCollectionContract
     * @throws InvalidResourceDefinition
     */
    public function identifiersFromInput(
        $resourceDefinition,
        ContextContract $context,
        $request = null
    ) : IdentifierCollectionContract
    {
        $resourceDefinitionFactory = $this->getResourceDefinitionFactory($resourceDefinition);

        $identifiers = $context->getInputParser()->getIdentifiers($this, $resourceDefinitionFactory, $context, $request);

        if (!$identifiers) {
            throw new \InvalidArgumentException("No data found in body");
        }

        return $identifiers;
    }
}
