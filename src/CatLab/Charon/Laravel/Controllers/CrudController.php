<?php

namespace CatLab\Charon\Laravel\Controllers;


use CatLab\Charon\Collections\ResourceCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\ResourceException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\ResourceResponse;
use CatLab\Charon\Models\RESTResource;
use CatLab\Requirements\Exceptions\ResourceValidationException;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CRUDController
 *
 * This trait contains some basic functionality to easily set up a laravel crud controller.
 * Any class using this trait must also use the ResourceController trait.
 *
 * @package CatLab\Charon\Laravel\Controllers
 */
trait CrudController
{
    /*
     * Required methods
     */
    abstract function getContext($action = Action::VIEW, $parameters = []) : \CatLab\Charon\Interfaces\Context;
    abstract function getResourceDefinition(): ResourceDefinition;

    abstract function getModels($queryBuilder, Context $context, $resourceDefinition = null, $records = null);

    abstract function bodyToResource(Context $context, $resourceDefinition = null) : RESTResource;
    abstract function bodyToResources(Context $context, $resourceDefinition = null) : ResourceCollection;

    abstract function getValidationErrorResponse(ResourceValidationException $e);
    abstract function notFound($id, $resource);
    abstract function toEntity(RESTResource $resource, Context $context, $existingEntity = null, $resourceDefinition = null, $entityFactory = null);

    abstract function toResources($entities, Context $context, $resourceDefinition = null) : ResourceCollection;
    abstract function toResource($entity, Context $context, $resourceDefinition = null) : RESTResource;

    use AuthorizesRequests {
        authorize as laravelAuthorize;
    }

    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        if (!defined('static::RESOURCE_DEFINITION')) {
            throw new ResourceException("All classes using CrudController must define a constant called RESOURCE_DEFINITION");
        }

        parent::__construct(static::RESOURCE_DEFINITION);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize(Action::INDEX, $this->getEntityClassName());
        $context = $this->getContext(Action::INDEX);

        $models = $this->getModels($this->callEntityMethod('query'), $context);
        $resources = $this->toResources($models, $context);

        return new ResourceResponse($resources, $context);
    }

    /**
     * View an entity
     * @param Request $request
     * @return Response
     */
    public function view(Request $request)
    {
        $entity = $this->findEntity($request);
        $this->authorize(Action::VIEW, $entity);

        return $this->createViewEntityResponse($entity);
    }

    /**
     * Create a new entity
     * @return Response
     */
    public function store(Request $request)
    {
        $this->authorize(Action::CREATE, $this->getEntityClassName());

        $writeContext = $this->getContext(Action::CREATE);
        $inputResource = $this->bodyToResource($writeContext);

        try {
            $inputResource->validate();
        } catch (ResourceValidationException $e) {
            return $this->getValidationErrorResponse($e);
        }

        $entity = $this->toEntity($inputResource, $writeContext);

        // Save the entity
        $entity->save();

        // Turn back into a resource
        return $this->createViewEntityResponse($entity);
    }

    /**
     * @param Request $request
     * @return ResourceResponse
     */
    public function edit(Request $request)
    {
        $entity = $this->findEntity($request);
        $this->authorize(Action::EDIT, $entity);

        $writeContext = $this->getContext(Action::EDIT);
        $inputResource = $this->bodyToResource($writeContext);

        try {
            $inputResource->validate();
        } catch (ResourceValidationException $e) {
            return $this->getValidationErrorResponse($e);
        }

        $entity = $this->toEntity($inputResource, $writeContext, $entity);

        // Save the entity
        $entity->save();

        // Turn back into a resource
        return $this->createViewEntityResponse($entity);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function destroy(Request $request)
    {
        $entity = $this->findEntity($request);
        $this->authorize('destroy', $entity);

        $entity->delete();

        return \Illuminate\Http\Response::json([
            'success' => true,
            'message' => 'Successfully deleted entity.'
        ]);
    }

    /**
     *
     * @param $entity
     * @return ResourceResponse
     */
    protected function createViewEntityResponse($entity)
    {
        $readContext = $this->getContext(Action::VIEW);
        $resource = $this->toResource($entity, $readContext);

        return new ResourceResponse($resource, $readContext);
    }

    /**
     * @return string
     */
    protected function getEntityClassName()
    {
        return $this->getResourceDefinition()->getEntityClassName();
    }

    /**
     * Call a static method on the entity.
     * @param $method
     * @return mixed
     */
    protected function callEntityMethod($method)
    {
        // We don't want to include the first argument.
        $args = func_get_args();
        array_shift($args);

        return call_user_func_array([ $this->getEntityClassName(), $method ], $args);
    }

    protected function findEntity(Request $request)
    {
        $id = $request->route()->parameter($this->getIdParameter());

        $entity = $this->callEntityMethod('find', $id);

        if (!$entity) {
            $this->notFound($id, $this->getEntityClassName());
        }

        return $entity;
    }

    /**
     * @return string
     */
    protected function getIdParameter()
    {
        if (defined('static::RESOURCE_ID')) {
            return static::RESOURCE_ID;
        } else {
            return 'id';
        }
    }
}