<?php

namespace CatLab\Charon\Models\Routing;
use CatLab\Charon\Exceptions\InvalidPropertyException;

/**
 * Class MatchedRoute
 *
 * Helper class primarily to host the example scripts.
 *
 * @package CatLab\Charon\Models\Routing
 */
class MatchedRoute
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var mixed[]
     */
    private $parameters;

    /**
     * MatchedRoute constructor.
     * @param Route $route
     * @param $parameters
     */
    public function __construct(Route $route, array $parameters)
    {
        $this->route = $route;
        $this->parameters = $parameters;
    }


    /**
     * @return mixed
     * @throws InvalidPropertyException
     */
    public function getControllerClass()
    {
        $action = explode('@', $this->route->getAction());
        if (count($action) !== 2) {
            throw InvalidPropertyException::makeTranslatable('Route action must be of type ClassName@action');
        }
        return $action[0];
    }

    /**
     * @return mixed
     * @throws InvalidPropertyException
     */
    public function getControllerAction()
    {
        $action = explode('@', $this->route->getAction());
        if (count($action) !== 2) {
            throw InvalidPropertyException::makeTranslatable('Route action must be of type ClassName@action.');
        }
        return $action[1];
    }

    /**
     * @return \mixed[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
