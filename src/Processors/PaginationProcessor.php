<?php

namespace CatLab\Charon\Processors;

use CatLab\Base\Interfaces\Database\SelectQueryParameters;
use CatLab\Base\Interfaces\Pagination\PaginationBuilder;
use CatLab\Base\Models\Database\OrderParameter;
use CatLab\Base\Models\Database\DB;
use CatLab\Charon\Interfaces\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RESTResource;
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

    /**
     * @var string[]
     */
    private $qualifiedNameMap = [];

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
     * @param int $records
     * @return void
     */
    public function processFilters(
        ResourceTransformer $transformer,
        SelectQueryParameters $queryBuilder,
        $request,
        ResourceDefinition $definition,
        Context $context,
        int $records = null
    ) {
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, $context, $request);

        if (isset($records)) {
            $builder->limit($records);
        } else {
            $limit = $transformer->getRequestResolver()->getRecords($request);
            if ($limit === null) {
                $limit = 10;
            }

            $builder->limit($limit);
        }

        // Build the filters
        $builder->build($queryBuilder);
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceCollection $collection
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param RelationshipValue $parent
     * @param null $parentEntity
     */
    public function processCollection(
        ResourceTransformer $transformer,
        ResourceCollection $collection,
        ResourceDefinition $definition,
        Context $context,
        RelationshipValue $parent = null,
        $parentEntity = null
    ) {
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, $context, null);

        if (
            count($collection) > 0 &&
            (
                // If records is null, ALL records are returned.
                // And if all records are returned, we don't need no (dum dum dum) pagi-na-tion
                !isset($parent) ||
                $parent->getField()->getRecords() > 0
            )
        ) {

            // Register all identifiers if parent is present
            if ($parent) {
                foreach ($definition->getFields()->getIdentifiers() as $field) {
                    $this->registerPropertyName($builder, $field, $context);
                    $registeredFields[$field->getDisplayName()] = $field;
                }
            }

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

            $collection->addMeta('pagination', [
                'next' => $cursor->getNext() ? $url . '?' . http_build_query($cursor->getNext()) : null,
                'previous' => $cursor->getPrevious() ? $url . '?' . http_build_query($cursor->getPrevious()) : null,
                'cursors' => $cursor->toArray()
            ]);
        }
    }

    /**
     * @param PaginationBuilder $builder
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

                        $builder->orderBy(
                            new OrderParameter(
                                $field->getName(),
                                $direction,
                                $definition->getEntityClassName()
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

        // Add all
        foreach ($definition->getFields() as $field) {
            if ($field instanceof IdentifierField && !isset($sortedOn[$field->getName()])) {

                $builder->orderBy(
                    new OrderParameter(
                        $field->getName(),
                        OrderParameter::ASC,
                        $definition->getEntityClassName()
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
