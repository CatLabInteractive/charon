<?php

declare(strict_types=1);

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
    private \CatLab\Charon\Models\Routing\Route $route;

    /**
     * @var mixed[]
     */
    private array $parameters;

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
    public function getControllerClass(): string
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
    public function getControllerAction(): string
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
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
