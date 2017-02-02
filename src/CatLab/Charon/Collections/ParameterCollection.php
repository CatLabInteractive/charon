<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\BodyParameter;
use CatLab\Charon\Models\Routing\Parameters\FileParameter;
use CatLab\Charon\Models\Routing\Parameters\PathParameter;
use CatLab\Charon\Models\Routing\Parameters\PostParameter;
use CatLab\Charon\Models\Routing\Parameters\QueryParameter;
use CatLab\Charon\Models\Routing\Route;

/**
 * Class ParameterCollection
 * @package CatLab\RESTResource\Collections
 */
class ParameterCollection
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var Parameter[]
     */
    private $parameters;

    /**
     * ParameterCollection constructor.
     * @param RouteMutator $route
     */
    public function __construct(RouteMutator $route)
    {
        $this->route = $route;
        $this->parameters = [];
    }

    /**
     * @param string $name
     * @return PathParameter
     */
    public function path($name)
    {
        $parameter = new PathParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * @param string $name
     * @return BodyParameter
     */
    public function body($name)
    {
        $parameter = new BodyParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * @param string $name
     * @return QueryParameter
     */
    public function query($name)
    {
        $parameter = new QueryParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * @param string $name
     * @return PostParameter
     */
    public function post($name)
    {
        $parameter = new PostParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * Creates a set of post parameters from a given resource definition.
     * @param $resourceDefinition
     * @param Context $context
     * @return RouteMutator
     */
    public function postParametersFromResourceDefinition($resourceDefinition, Context $context = null)
    {
        if (!$context) {
            $context = new \CatLab\Charon\Models\Context(
                Method::toAction($this->route->getMethod(), Cardinality::ONE)
            );
        }

        $resourceDefinition = ResourceDefinitionLibrary::make($resourceDefinition);

        foreach ($resourceDefinition->getFields() as $field) {

            if ($field instanceof RelationshipField) {
                continue;
            }

            /** @var Field $field */
            if ($field->hasAction($context->getAction())) {
                $this->postParameterFromField($field, $context);
            }
        }

        return $this->route;
    }

    /**
     * @param Field $field
     * @param Context $context
     * @return PostParameter
     */
    public function postParameterFromField(Field $field, Context $context)
    {
        $post = $this->post($field->getDisplayName());
        $post->setType($field->getType());
        $post->required($field->isRequired());

        return $post;
    }

    /**
     * @param $name
     * @return FileParameter
     */
    public function file($name)
    {
        $parameter = new FileParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * @return \CatLab\Charon\Models\Routing\Parameters\Base\Parameter[]
     */
    public function toMap()
    {
        return $this->parameters;
    }

    /**
     * @return \CatLab\Charon\Models\Routing\Parameters\Base\Parameter[]
     */
    public function toArray()
    {
        return array_values($this->parameters);
    }
}