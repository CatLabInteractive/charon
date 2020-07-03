<?php

namespace CatLab\Charon\Processors;

use CatLab\Base\Interfaces\Database\SelectQueryParameters;
use CatLab\Base\Interfaces\Pagination\PaginationBuilder;
use CatLab\Base\Models\Database\OrderParameter;
use CatLab\Base\Models\Database\DB;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Interfaces\Context as ContextContract;
use CatLab\Charon\Interfaces\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\ResourceDefinitionFactory;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Models\FilterResults;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\HasRequestResolver;
use CatLab\Charon\Models\Values\Base\RelationshipValue;

/**
 * Class PaginationProcessor
 * @package CatLab\RESTResource\Processors
 */
class PaginationProcessor implements Processor
{
    /**
     * @var PaginationBuilder
     */
    private $paginationBuilders;

    /**
     * @var string
     */
    private $paginationClass;

    const RANDOM = 'random';
    const RANDOM_SEED_QUERY = 'seed';

    const MAX_INT = 2147483647;

    /**
     * PaginationProcessor constructor.
     * @param string $builder
     */
    public function __construct(string $builder)
    {
        $this->paginationClass = $builder;
        $this->paginationBuilders = [];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param $queryBuilder
     * @param $request
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param FilterResults $filterResults
     * @return mixed|void
     * @throws NotImplementedException
     */
    public function processFilters(
        ResourceTransformer $transformer,
        $queryBuilder,
        $request,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults
    ) {
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, $context, $request);

        // the amount of records we want.
        $records = intval($transformer->getRequestResolver()->getRecords($request));
        if ($records < 1) {
            $records = 10;
        }
        $builder->limit($records);

        // First count the total amount of records
        $totalAmountOfRecords = $transformer->getQueryAdapter()->countRecords(
            $transformer,
            $definition,
            $context,
            $queryBuilder
        );

        $filterResults->setTotalRecords($totalAmountOfRecords);
        $filterResults->setRecords($records);

        // Build the filters
        $catlabQueryBuilder = new \CatLab\Base\Models\Database\SelectQueryParameters();
        $builder->build($catlabQueryBuilder);

        $this->processProcessorFilters($transformer, $context, $definition, $catlabQueryBuilder, $queryBuilder);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param Context $context
     * @param ResourceDefinition $resourceDefinition
     * @param SelectQueryParameters $filter
     * @param null $queryBuilder
     * @throws NotImplementedException
     */
    protected function processProcessorFilters(
        ResourceTransformer $transformer,
        Context $context,
        ResourceDefinition $resourceDefinition,
        SelectQueryParameters $filter,
        $queryBuilder = null
    ) {
        $where = $filter->getWhere();
        if (count($where) > 0) {
            throw new NotImplementedException('NOT IMPLEMENTED YET!');
        }

        // now we need to translate these to our own system
        foreach ($filter->getSort() as $sort) {
            $entity = $sort->getEntity();
            if ($entity instanceof Field) {
                $entity->setRequiredForProcessor();

                $transformer->getQueryAdapter()->applyPropertySorting(
                    $transformer,
                    $entity->getResourceDefinition(),
                    $context,
                    $entity,
                    $queryBuilder,
                    $sort->getDirection()
                );
            }
        }

        $limit = $filter->getLimit();
        if ($limit) {
            $transformer->getQueryAdapter()->applyLimit(
                $transformer,
                $resourceDefinition,
                $context,
                $queryBuilder,
                $limit->getAmount(),
                $limit->getOffset()
            );
        }
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition|ResourceDefinition[] $definition
     * @param Context $context
     * @param FilterResults|null $filterResults
     * @param RelationshipValue $parent
     * @param null $parentEntity
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinitionFactory $definition,
        Context $context,
        FilterResults $filterResults = null,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        list ($url, $cursor) = $this->prepareCursor(
            $transformer,
            $collection,
            $definition->getDefault(),
            $context,
            $filterResults,
            $parent,
            $parentEntity
        );

        $collection->addMeta('pagination', [
            'next' => $cursor && $cursor->getNext() ? $url . '?' . http_build_query($cursor->getNext()) : null,
            'previous' => $cursor && $cursor->getPrevious() ? $url . '?' . http_build_query($cursor->getPrevious()) : null,
            'cursors' => $cursor ? $cursor->toArray() : null
        ]);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param FilterResults|null $filterResults
     * @param RelationshipValue|null $parent
     * @param null $parentEntity
     * @return array|null
     */
    protected function prepareCursor(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        FilterResults $filterResults = null,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, $context, null);

        if (
            count($collection) === 0 ||
            (
                // If records is null, ALL records are returned.
                // And if all records are returned, we don't need no (dum dum dum) pagi-na-tion
                isset($parent) &&
                $parent->getField()->getRecords() === 0
            )
        ) {
            return null;
        }

        // Register all identifiers if parent is present
        if ($parent) {
            foreach ($definition->getFields()->getIdentifiers() as $field) {
                $this->registerPropertyName($builder, $field, $context);
                $registeredFields[$field->getDisplayName()] = $field;
            }
        }

        $builder->processCollection($collection, $filterResults);

        /** @var RESTResource $first */
        $first = $collection->first();
        if ($first) {
            $builder->setFirst($this->transformResource($transformer, $builder, $context, $first));
        }

        /** @var RESTResource $first */
        $last = $collection->last();
        if ($last) {
            $builder->setLast($this->transformResource($transformer, $builder, $context, $last));
        }

        $cursor = $builder->getNavigation();

        if ($parent) {
            $url = $parent->getField()->getUrl();
        } elseif($context->getUrl()) {
            $url = $context->getUrl();
        } else {
            $url = $definition->getUrl();
        }

        $url = $transformer->getPropertyResolver()->resolvePathParameters(
            $transformer,
            $parentEntity,
            $url,
            $context
        );

        return [ $url, $cursor ];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param PaginationBuilder $builder
     * @param Context $context
     * @param RESTResource $resource
     * @return mixed
     */
    private function transformResource(
        ResourceTransformer $transformer,
        PaginationBuilder $builder,
        Context $context,
        RESTResource $resource
    ) {
        $sortOrder = $builder->getOrderBy();
        $properties = $resource->getProperties();

        $out = [];

        foreach ($sortOrder as $sort) {

            // check if we have to tranlsate the fully qualified name to a display name

            $value = $properties->getFromName($sort->getColumn());
            if ($value) {
                $out[$sort->getColumn()] = $value->getValue();
            }
        }

        // Also add identifiers
        foreach ($resource->getProperties()->getIdentifiers()->getValues() as $identifier) {
            if (!isset($out[$identifier->getField()->getName()])) {
                $out[$transformer->getQualifiedName($identifier->getField())] = $identifier->getValue();
            }
        }

        return $out;
    }

    /**
     * @param ResourceTransformer $transformer
     * @param RESTResource $resource
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     */
    public function processResource(
        ResourceTransformer $transformer,
        RESTResource $resource,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        // Nothing to do here...
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param null $request
     * @return PaginationBuilder
     */
    private function getPaginationBuilderFromDefinition(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $request = null
    ) {
        $className = get_class($definition);

        if (!isset($this->paginationBuilders[$className])) {
            $this->paginationBuilders[$className] = $this->createPaginationBuilderFromDefinition(
                $transformer,
                $definition,
                $context,
                $request
            );
        }

        return $this->paginationBuilders[$className];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param null $request
     * @return PaginationBuilder
     */
    private function createPaginationBuilderFromDefinition(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        Context $context,
        $request = null
    ) {
        $cn = $this->paginationClass;

        /**
         * @var PaginationBuilder $builder
         */
        $builder = new $cn();
        if ($builder instanceof HasRequestResolver) {
            $builder->setRequestResolver($transformer->getRequestResolver());
        }

        $registeredFields = [];

        // Register attribute names
        foreach ($definition->getFields() as $field) {
            if (
                $field instanceof IdentifierField ||
                (
                    $field instanceof ResourceField &&
                    $field->isSortable()
                )
            ) {
                $this->registerPropertyName($builder, $field, $context);
                $registeredFields[$field->getDisplayName()] = $field;
            }
        }

        $sorting = $transformer->getRequestResolver()->getSorting($request);

        $sortedOn = [];
        $sortDirections = [];

        if ($sorting) {
            $sortFields = explode(',', $sorting);

            // Set the sort order
            foreach ($sortFields as $sortField) {
                if (mb_substr($sortField, 0, 1) === '!') {
                    $sortField = mb_substr($sortField, 1);
                    $direction = OrderParameter::DESC;
                } else {
                    $direction = OrderParameter::ASC;
                }

                $field = $registeredFields[$sortField] ?? null;

                if ($field) {
                    if ($field->isSortable()) {
                        $sortedOn[$field->getName()] = true;
                        $sortDirections[] = $direction;

                        $builder->orderBy(
                            new OrderParameter(
                                $field->getName(),
                                $direction,
                                $field
                            )
                        );
                    }
                } else {
                    // Check sortable
                    switch($sortField) {

                        case self::RANDOM:
                            $this->handleRandomOrder(
                                $builder,
                                $definition,
                                $direction,
                                $request
                            );

                            break;

                    }
                }
            }
        }

        // Add all identifiers (and check the direction of the first parameter so we can switch that around)
        foreach ($definition->getFields() as $field) {
            if ($field instanceof IdentifierField && !isset($sortedOn[$field->getName()])) {

                $defaultSortDirection = isset($sortDirections[0]) ? $sortDirections[0] : OrderParameter::ASC;

                $builder->orderBy(
                    new OrderParameter(
                        $field->getName(),
                        $defaultSortDirection,
                        $field
                    )
                );
            }
        }

        // Set request
        if (isset($request)) {
            $builder->setRequest($request, $transformer->getRequestResolver());
        }

        return $builder;
    }

    /**
     * @param PaginationBuilder $builder
     * @param Field $field
     * @param Context $context
     */
    protected function registerPropertyName(PaginationBuilder $builder, Field $field, Context $context)
    {
        $builder->registerPropertyName(
            $field->getName(),
            $field->getDisplayName(),
            function($value) use ($field, $context) {
                return $field->getTransformer()->toEntityValue($value, $context);
            });
    }

    /**
     * @param PaginationBuilder $builder
     * @param ResourceDefinition $definition
     * @param $direction
     * @param $request
     */
    private function handleRandomOrder(PaginationBuilder $builder, ResourceDefinition $definition, $direction, &$request)
    {
        if (isset($request) && isset($request[self::RANDOM_SEED_QUERY])) {
            $random = intval($request[self::RANDOM_SEED_QUERY]);
        } else {
            $random = mt_rand(0, self::MAX_INT);
        }

        $builder->orderBy(
            new OrderParameter(
                DB::raw('RAND(' . $random . ')'),
                $direction,
                $definition
            )
        );

        $request[self::RANDOM_SEED_QUERY] = $random;
    }
}
