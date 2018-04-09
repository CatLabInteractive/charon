<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Enums\Method;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\BodyParameter;
use CatLab\Charon\Models\Routing\Parameters\FileParameter;
use CatLab\Charon\Models\Routing\Parameters\HeaderParameter;
use CatLab\Charon\Models\Routing\Parameters\PathParameter;
use CatLab\Charon\Models\Routing\Parameters\PostParameter;
use CatLab\Charon\Models\Routing\Parameters\QueryParameter;
use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
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
     * @deprecated Use resource() to define resource input.
     * @param string $name
     * @return BodyParameter
     */
    public function body($name)
    {
        if ($name instanceof ResourceDefinition) {
            $name = get_class($name);
        }

        $parameter = new BodyParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * Define the resource that can be sent with the request.
     * Note that this method will not generate actual parameters, but instead
     * will let the InputParsers know this resource can be expected.
     * @param $resourceDefinition
     * @return ResourceParameter
     */
    public function resource($resourceDefinition)
    {
        $parameter = new ResourceParameter($resourceDefinition);
        $parameter->setRoute($this->route);

        $this->parameters[$resourceDefinition] = $parameter;

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

    public function header($name)
    {
        $parameter = new HeaderParameter($name);
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

    /**
     * @param ParameterCollection $collection
     * @return array
     */
    public function merge(ParameterCollection $collection)
    {
        $this->parameters = array_merge($this->parameters, $collection->parameters);
    }
}