<?php

namespace CatLab\Charon\Laravel\Controllers;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Base\Models\Database\WhereParameter;
use CatLab\Base\Models\Grammar\AndConjunction;
use CatLab\Base\Models\Grammar\OrConjunction;
use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Factories\EntityFactory;
use CatLab\Charon\Laravel\InputParsers\JsonBodyInputParser;
use CatLab\Charon\Laravel\InputParsers\PostInputParser;
use CatLab\Laravel\Database\SelectQueryTransformer;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\ResourceTransformer as ResourceTransformerContract;

use CatLab\Charon\Laravel\Resolvers\PropertyResolver;
use CatLab\Charon\Laravel\Resolvers\PropertySetter;
use CatLab\Charon\Laravel\Transformers\ResourceTransformer;
use CatLab\Charon\Models\RESTResource;
use CatLab\Requirements\Exceptions\ResourceValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;

use Request;
use Response;

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
        $filter = $this->resourceTransformer->getFilters(
            Request::query(),
            $resourceDefinition,
            $context,
            $records
        );

        // Translate parameters to larevel query
        SelectQueryTransformer::toLaravel($model, $filter);

        // Process eager loading
        $this->resourceTransformer->processEagerLoading($model, $resourceDefinition, $context);

        if (
            $model instanceof Builder ||
            $model instanceof Relation
        ) {
            $models = $model->get();
        } else {
            $models = $model;
        }

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
     * @return \CatLab\Charon\Interfaces\RESTResource
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
     * @return ResourceCollection
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
        $resources = $this->bodyToResources($context, $resourceDefinition);
        return $resources->first();
    }

    /**
     * @param Context $context
     * @param null $resourceDefinition
     * @return \CatLab\Charon\Collections\ResourceCollection
     */
    public function bodyToResources(Context $context, $resourceDefinition = null)
    {
        $resourceDefinition = $resourceDefinition ?? $this->resourceDefinition;
        return $this->resourceTransformer->fromInput($resourceDefinition, $context);
    }

    /**
     * @param Context $context
     * @param $resourceDefinition
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function bodyIdentifiersToEntities(Context $context, $resourceDefinition = null)
    {
        $resourceDefinition = $resourceDefinition ?? $this->resourceDefinition;

        $identifiers = $this->resourceTransformer->identifiersFromInput(
            $resourceDefinition,
            $context
        );

        return $this->resourceTransformer->entitiesFromIdentifiers(
            $resourceDefinition,
            $identifiers,
            new EntityFactory(),
            $context
        );
    }

    /**
     * @param string $action
     * @param array $parameters
     * @return Context|string
     */
    protected function getContext($action = Action::VIEW, $parameters = [])
    {
        $context = new \CatLab\Charon\Models\Context($action, $parameters);

        if ($toShow = \Request::input(ResourceTransformer::FIELDS_PARAMETER)) {
            $context->showFields(array_map('trim', explode(',', $toShow)));
        }

        if ($toExpand = \Request::input(ResourceTransformer::EXPAND_PARAMETER)) {
            $context->expandFields(array_map('trim', explode(',', $toExpand)));
        }

        $context->setUrl(\Request::url());
        $this->setInputParsers($context);

        return $context;
    }

    /**
     * Set the input parsers that will be used to turn requests into resources.
     * @param \CatLab\Charon\Models\Context $context
     */
    protected function setInputParsers(\CatLab\Charon\Models\Context $context)
    {
        $context->addInputParser(JsonBodyInputParser::class);
        // $context->addInputParser(PostInputParser::class);
    }

    /**
     * @param int $id
     * @param string $resource
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound($id, $resource)
    {
        if ($resource) {
            throw new ModelNotFoundException('Resource ' . $id . ' ' . $resource . ' not found.');
        } else {
            throw new ModelNotFoundException('Resource ' . $id . ' not found.');
        }
    }


    /**
     * Output a resource or a collection of resources
     *
     * @param $models
     * @param array $parameters
     * @param null $resourceDefinition
     * @return \Illuminate\Http\JsonResponse
     */
    protected function outputList($models, array $parameters = [], $resourceDefinition = null)
    {
        $resources = $this->filteredModelsToResources($models, $parameters, $resourceDefinition);
        return $this->toResponse($resources);
    }

    /**
     * @param $models
     * @param array $parameters
     * @param null $resourceDefinition
     * @return array|\mixed[]
     */
    protected function filteredModelsToResources($models, array $parameters = [], $resourceDefinition = null)
    {
        $resourceDefinition = $resourceDefinition ?? $this->resourceDefinition;

        $context = $this->getContext(Action::INDEX, $parameters);

        $records = Request::input('records', 10);
        if (!is_numeric($records)) {
            $records = 10;
        }

        $models = $this->filterAndGet(
            $models,
            $resourceDefinition,
            $context,
            $records
        );

        return $this->modelsToResources($models, $context, $resourceDefinition);
    }


    /**
     * Output a resource or a collection of resources
     *
     * @param $models
     * @param array $parameters
     * @return \Illuminate\Http\JsonResponse
     */
    protected function output($models, array $parameters = [])
    {
        if (ArrayHelper::isIterable($models)) {
            $context = $this->getContext(Action::INDEX, $parameters);
        } else {
            $context = $this->getContext(Action::VIEW, $parameters);
        }

        $output = $this->modelsToResources($models, $context);
        return $this->toResponse($output);
    }

    /**
     * @param Model|Model[] $models
     * @param Context $context
     * @param null $resourceDefinition
     * @return array|\mixed[]
     */
    protected function modelsToResources($models, Context $context, $resourceDefinition = null)
    {
        if (ArrayHelper::isIterable($models)) {
            return $this->toResources($models, $context, $resourceDefinition)->toArray();
        } elseif ($models instanceof Model) {
            return $this->toResource($models, $context, $resourceDefinition)->toArray();
        } else {
            return $models;
        }
    }

    /**
     * @param ResourceValidationException $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getValidationErrorResponse(ResourceValidationException $e)
    {
        return $this->toResponse([
            'error' => [
                'message' => 'Could not decode resource.',
                'issues' => $e->getMessages()->toMap()
            ]
        ])->setStatusCode(400);
    }

    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function toResponse($data)
    {
        return Response::json($data);
    }
}