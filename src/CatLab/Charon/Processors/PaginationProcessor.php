<?php

namespace CatLab\Charon\Processors;

use CatLab\Base\Interfaces\Pagination\PaginationBuilder;
use CatLab\Base\Models\Database\SelectQueryParameters;
use CatLab\Base\Models\Database\OrderParameter;
use CatLab\Charon\Collections\PropertyValues;
use CatLab\Base\Models\Database\DB;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Processor;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Interfaces\RESTResource;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Interfaces\ResourceDefinition;
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
     * @param SelectQueryParameters $parameters
     * @param $request
     * @param ResourceDefinition $definition
     * @param Context $context
     * @param int $records
     * @return void
     */
    public function processFilters(
        ResourceTransformer $transformer,
        SelectQueryParameters $parameters,
        $request,
        ResourceDefinition $definition,
        Context $context,
        int $records = null
    ) {
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, $request);

        if (isset($records)) {
            $builder->limit($records);
        } else {
            $builder->limit(
                $transformer->getPropertyResolver()->getParameterFromRequest(
                    $request,
                    ResourceTransformer::LIMIT_PARAMETER,
                    10
                )
            );
        }

        // Build the filters
        $builder->build($parameters);
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
        $builder = $this->getPaginationBuilderFromDefinition($transformer, $definition, null, null);

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
                    $builder->registerPropertyName(
                        $field->getName(),
                        $field->getDisplayName()
                    );
                }
            }

            /** @var RESTResource $first */
            $first = $collection->first();
            if ($first) {
                $builder->setFirst($this->transformResource($builder, $first));
            }

            /** @var RESTResource $first */
            $last = $collection->last();
            if ($last) {
                $builder->setLast($this->transformResource($builder, $last));
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
    private function transformResource(PaginationBuilder $builder, RESTResource $resource)
    {
        $sortOrder = $builder->getOrderBy();
        $properties = $resource->getProperties();

        $out = [];
        
        foreach ($sortOrder as $sort) {
            $value = $properties->getFromName($sort->getColumn());
            if ($value) {
                $out[$value->getField()->getName()] = $value->getValue();
            }
        }

        // Also add identifiers
        foreach ($resource->getProperties()->getIdentifiers()->getValues() as $identifier) {
            if (!isset($out[$identifier->getField()->getName()])) {
                $out[$identifier->getField()->getName()] = $identifier->getValue();
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
     * @param null $request
     * @return PaginationBuilder
     */
    private function getPaginationBuilderFromDefinition(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        $request = null
    ) {
        $className = get_class($definition);

        if (!isset($this->paginationBuilders[$className])) {
            $this->paginationBuilders[$className] = $this->createPaginationBuilderFromDefinition(
                $transformer,
                $definition,
                $request
            );
        }

        return $this->paginationBuilders[$className];
    }

    /**
     * @param ResourceTransformer $transformer
     * @param ResourceDefinition $definition
     * @param null $request
     * @return PaginationBuilder
     */
    private function createPaginationBuilderFromDefinition(
        ResourceTransformer $transformer,
        ResourceDefinition $definition,
        $request = null
    ) {
        $cn = $this->paginationClass;

        /**
         * @var PaginationBuilder $builder
         */
        $builder = new $cn();

        // Register attribute names
        foreach ($definition->getFields() as $field) {
            if (
                $field instanceof IdentifierField ||
                (
                    $field instanceof ResourceField &&
                    $field->isSortable()
                )
            ) {
                $builder->registerPropertyName($field->getName(), $field->getDisplayName());
            }
        }

        $sorting = $transformer->getPropertyResolver()->getParameterFromRequest(
            $request,
            ResourceTransformer::SORT_PARAMETER
        );

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

                $field = $definition->getFields()->getFromDisplayName($sortField);

                if ($field) {
                    if ($field->isSortable()) {
                        $sortedOn[$field->getName()] = true;
                        $builder->orderBy(new OrderParameter($field->getName(), $direction));
                    }
                } else {
                    // Check sortable
                    switch($sortField) {

                        case self::RANDOM:
                            $this->handleRandomOrder(
                                $builder,
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
                $builder->orderBy(new OrderParameter($field->getName(), OrderParameter::ASC));
            }
        }

        // Set request
        if (isset($request)) {
            $builder->setRequest($request);
        }

        return $builder;
    }

    /**
     * @param PaginationBuilder $builder
     * @param $direction
     * @param $request
     */
    private function handleRandomOrder(PaginationBuilder $builder, $direction, &$request)
    {
        if (isset($request) && isset($request[self::RANDOM_SEED_QUERY])) {
            $random = intval($request[self::RANDOM_SEED_QUERY]);
        } else {
            $random = mt_rand(0, PHP_INT_MAX);
        }


        $builder->orderBy(new OrderParameter(
                DB::raw('RAND(' . $random . ')'),
                $direction)
        );

        $request[self::RANDOM_SEED_QUERY] = $random;
    }
}