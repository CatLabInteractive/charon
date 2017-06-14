<?php

namespace CatLab\Charon\Laravel\Controllers;


use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\ResourceException;
use CatLab\Charon\Models\ResourceResponse;
use CatLab\Requirements\Exceptions\ResourceValidationException;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    use ResourceController;
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
     * @return Response
     */
    public function index()
    {
        $this->authorize('index');
        $context = $this->getContext(Action::INDEX);

        $models = $this->getModels($this->callEntityMethod('query'), $context);
        $resources = $this->toResources($models, $context);

        return new ResourceResponse($resources, $context);
    }

    /**
     * View an entity
     * @param $id
     * @return Response
     */
    public function view($id)
    {
        $entity = $this->callEntityMethod('find', $id);
        $this->authorize('view', $entity);

        if (!$entity) {
            return $this->notFound($id, $this->getEntityClassName());
        }

        return $this->createViewEntityResponse($entity);
    }

    /**
     * Create a new entity
     * @return Response
     */
    public function create()
    {
        $this->authorize('create');

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
     * Authorize a method call.
     * @param $ability
     * @param array $arguments
     * @param string|null $entityClassName
     * @return mixed
     */
    public function authorize($ability, $arguments = [], string $entityClassName = null)
    {
        if ($entityClassName === null) {
            $entityClassName = $this->getResourceDefinition()->getEntityClassName();
        }

        array_unshift($arguments, $entityClassName);

        return $this->laravelAuthorize($ability, ...$arguments);
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
}