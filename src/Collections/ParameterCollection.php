<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\RouteMutator;
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
    private \CatLab\Charon\Interfaces\RouteMutator $route;

    /**
     * @var Parameter[]
     */
    private array $parameters = [];

    /**
     * ParameterCollection constructor.
     * @param RouteMutator $route
     */
    public function __construct(RouteMutator $route)
    {
        $this->route = $route;
    }

    /**
     * @param string $name
     * @return PathParameter
     */
    public function path($name): \CatLab\Charon\Models\Routing\Parameters\PathParameter
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
    public function body($name): \CatLab\Charon\Models\Routing\Parameters\BodyParameter
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
    public function resource($resourceDefinition): \CatLab\Charon\Models\Routing\Parameters\ResourceParameter
    {
        if ($resourceDefinition instanceof ResourceDefinition) {
            $parameterKey = get_class($resourceDefinition);
        } elseif (is_string($resourceDefinition)) {
            $parameterKey = $resourceDefinition;
        } else {
            throw new \InvalidArgumentException('Resource parameter must either be a classname or a class object, ' . get_class($resourceDefinition) . ' provided.');
        }

        $parameter = new ResourceParameter($resourceDefinition);
        $parameter->setRoute($this->route);

        $this->parameters[$parameterKey] = $parameter;

        return $parameter;
    }

    /**
     * @param string $name
     * @return QueryParameter
     */
    public function query($name): \CatLab\Charon\Models\Routing\Parameters\QueryParameter
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
    public function post($name): \CatLab\Charon\Models\Routing\Parameters\PostParameter
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
    public function file($name): \CatLab\Charon\Models\Routing\Parameters\FileParameter
    {
        $parameter = new FileParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    public function header($name): \CatLab\Charon\Models\Routing\Parameters\HeaderParameter
    {
        $parameter = new HeaderParameter($name);
        $parameter->setRoute($this->route);

        $this->parameters[$name] = $parameter;

        return $parameter;
    }

    /**
     * @return \CatLab\Charon\Models\Routing\Parameters\Base\Parameter[]
     */
    public function toMap(): array
    {
        return $this->parameters;
    }

    /**
     * @return \CatLab\Charon\Models\Routing\Parameters\Base\Parameter[]
     */
    public function toArray(): array
    {
        return array_values($this->parameters);
    }

    /**
     * @param ParameterCollection $collection
     * @return array
     */
    public function merge(ParameterCollection $collection): void
    {
        $this->parameters = array_merge($this->parameters, $collection->parameters);
    }
}
