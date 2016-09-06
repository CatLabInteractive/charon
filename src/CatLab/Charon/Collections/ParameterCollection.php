<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\BodyParameter;
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