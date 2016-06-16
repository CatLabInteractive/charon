<?php

namespace CatLab\Charon\Laravel\Controllers;

use CatLab\Base\Models\Database\WhereParameter;
use CatLab\Base\Models\Grammar\AndConjunction;
use CatLab\Base\Models\Grammar\OrConjunction;
use CatLab\Charon\Factories\EntityFactory;
use CatLab\Laravel\Database\SelectQueryTransformer;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\ResourceTransformer as ResourceTransformerContract;

use CatLab\Charon\Laravel\Resolvers\PropertyResolver;
use CatLab\Charon\Laravel\Resolvers\PropertySetter;
use CatLab\Charon\Laravel\Transformers\ResourceTransformer;
use CatLab\Charon\Models\RESTResource;
use Illuminate\Database\Eloquent\Builder;
use Request;

/**
 * Class ResourceController
 * @package CatLab\RESTResource\Laravel\Controllers
 */
trait ResourceController
{
    /**
     * @var ResourceDefinitionContract
     */
    protected $resourceDefinition;

    /**
     * @var ResourceTransformer
     */
    protected $resourceTransformer;

    /**
     * @param $model
     * @param $resourceDefinition
     * @param Context $context
     * @param int $records
     * @return mixed
     */
    public function filterAndGet($model, $resourceDefinition, Context $context, $records = 10)
    {
        $filter = $this->resourceTransformer->getFilters(Request::input(), $resourceDefinition, $context, $records);

        // Translate parameters to larevel query
        SelectQueryTransformer::toLaravel($model, $filter);

        $models = $model->get();

        if ($filter->isReverse()) {
            $models = $models->reverse();
        }

        return $models;
    }

    /**
     * @param Builder $query
     * @param $wheres
     */
    private function processWhere(Builder $query, $wheres)
    {
        $self = $this;
        foreach ($wheres as $where) {

            /** @var WhereParameter $where */
            $query->where(function(Builder $query) use ($self, $where) {

                if ($comparison = $where->getComparison()) {
                    $query->where($comparison->getSubject(), $comparison->getOperator(), $comparison->getValue());
                }

                foreach ($where->getChildren() as $child) {
                    if ($child instanceof AndConjunction) {
                        $query->where(function (Builder $query) use ($self, $child) {
                            $this->processWhere($query, [ $child->getSubject() ]);
                        });
                    } elseif ($child instanceof OrConjunction) {
                        $query->orWhere(function (Builder $query) use ($self, $child) {
                            $this->processWhere($query, [ $child->getSubject() ]);
                        });
                    } else {
                        throw new \InvalidArgumentException("Got an unknown conjunction");
                    }
                }
            });
        }
    }

    /**
     * @param ResourceDefinitionContract $resourceDefinition
     * @param ResourceTransformerContract $resourceTransformer
     * @return $this
     */
    public function setResourceDefinition(
        ResourceDefinitionContract $resourceDefinition,
        ResourceTransformerContract $resourceTransformer = null
    ) {
        $this->resourceDefinition = $resourceDefinition;

        if (!isset($resourceTransformer)) {
            $this->resourceTransformer = new ResourceTransformer(
                new PropertyResolver(),
                new PropertySetter()
            );
        }

        return $this;
    }

    /**
     * @param mixed $entity
     * @param Context $context
     * @return RESTResource
     */
    public function toResource($entity, Context $context, $resourceDefinition = null)
    {
        return $this->resourceTransformer->toResource(
            $resourceDefinition ?? $this->resourceDefinition,
            $entity,
            $context
        );
    }

    /**
     * @param mixed $entities
     * @param Context $context
     * @param null $resourceDefinition
     * @return RESTResource
     * @throws \CatLab\Charon\Exceptions\InvalidEntityException
     */
    public function toResources($entities, Context $context, $resourceDefinition = null)
    {
        return $this->resourceTransformer->toResources(
            $resourceDefinition ?? $this->resourceDefinition,
            $entities,
            $context
        );
    }

    /**
     * @param Context $context
     * @param null $resourceDefinition
     * @return RESTResource
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function bodyToResource(Context $context, $resourceDefinition = null)
    {
        $content = Request::instance()->getContent();
        switch (mb_strtolower(Request::header('content-type'))) {
            case 'application/json':
            case 'text/json':
                $content = json_decode($content, true);

                if (!$content) {
                    throw new \InvalidArgumentException("Could not decode body.");
                }

                return $this->resourceTransformer->fromArray(
                    $resourceDefinition ?? $this->resourceDefinition,
                    $content,
                    $context
                );

            default:
                throw new \InvalidArgumentException("Could not decode body.");
        }
    }

    /**
     * @param Context $context
     * @param $resourceDefinition
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function bodyIdentifiersToResource(Context $context, $resourceDefinition = null)
    {
        $content = Request::instance()->getContent();
        switch (mb_strtolower(Request::header('content-type'))) {
            case 'application/json':
            case 'text/json':
                $content = json_decode($content, true);

                if (!$content) {
                    throw new \InvalidArgumentException("Could not decode body.");
                }

                return $this->resourceTransformer->fromIdentifiers(
                    $resourceDefinition ?? $this->resourceDefinition,
                    $content,
                    new EntityFactory(),
                    $context
                );

            default:
                throw new \InvalidArgumentException("Could not decode body.");
        }
    }
}