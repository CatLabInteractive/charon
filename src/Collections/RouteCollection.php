<?php

declare(strict_types=1);

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Method;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Routing\MatchedRoute;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Models\Routing\RouteProperties;
use CatLab\Charon\Models\StaticResourceDefinitionFactory;

/**
 * Class RouteCollection
 * @package CatLab\RESTResource\src\CatLab\RESTResource\Collections
 */
class RouteCollection extends RouteProperties implements \ArrayAccess
{
    public const OPTIONS_IDENTIFIER_NAME = 'id';

    public const OPTIONS_PARENT_IDENTIFIER_NAME = 'parentId';

    public const OPTIONS_IDENTIFIER_TRANSFORMER = 'identifier_transformer';

    public const OPTIONS_ONLY_INCLUDE_METHODS = 'only';

    public const OPTIONS_MAX_EXPAND_DEPTH = 'maxExpandDepth';

    // 'index', 'view', 'store', 'edit', 'destroy'
    public const OPTIONS_METHOD_INDEX = 'index';

    public const OPTIONS_METHOD_VIEW = 'view';

    public const OPTIONS_METHOD_STORE = 'store';

    public const OPTIONS_METHOD_EDIT = 'edit';

    public const OPTIONS_METHOD_DESTROY = 'destroy';

    public const OPTIONS_METHOD_PATCH = 'patch';

    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * @var RouteCollection[]
     */
    private array $children = [];

    /**
     * Helper to access routes in a collection more dynamically.
     */
    private array $namedRoutesMap = [];

    /**
     * RouteCollection constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * @param $options
     * @param callable $callback
     * @return RouteCollection
     */
    public function group($options, callable $callback = null): static
    {
        if (!isset($callback) && is_callable($options)) {
            $callback = $options;
            $options = [];
        }

        $child = $this->createRouteCollection($options);
        $child->setParent($this);

        $this->children[] = $child;

        if (isset($callback)) {
            call_user_func($callback, $child);
        }

        return $child;
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function get($path, $action, array $options = [], $name = null)
    {
        return $this->action('get', $path, $action, $options, $name);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function post($path, $action, array $options = [], $name = null)
    {
        return $this->action('post', $path, $action, $options, $name);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function put($path, $action, array $options = [], $name = null)
    {
        return $this->action('put', $path, $action, $options, $name);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function patch($path, $action, array $options = [], $name = null)
    {
        return $this->action('patch', $path, $action, $options, $name);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function delete($path, $action, array $options = [], $name = null)
    {
        return $this->action('delete', $path, $action, $options, $name);
    }

    /**
     * Represents a request to link resources with eachother
     * @param $path
     * @param $action
     * @param array $options
     * @param null|string $name
     * @return Route
     */
    public function link($path, $action, array $options = [], $name = null)
    {
        return $this->action(Method::LINK, $path, $action, $options, $name);
    }

    /**
     * Represents a request to link resources with eachother
     * @param $path
     * @param $action
     * @param array $options
     * @return Route
     */
    public function unlink($path, $action, array $options = [])
    {
        return $this->action(Method::UNLINK, $path, $action, $options);
    }

    /**
     * @param string $method
     * @param string $path
     * @param string|callable $action
     * @param mixed[] $options
     * @param string|null $name = null
     * @return Route
     */
    public function action($method, $path, $action, array $options = [], $name = null)
    {
        $route = $this->createRoute($method, $path, $action, $options);
        if (isset($name)) {
            $this->namedRoutesMap[$name] = $route;
        }

        $this->routes[] = $route;
        return $route;
    }

    /**
     * Set all crud actions, including documentation
     * Really only usable for the default case
     * @param $resourceDefinition
     * @param string $path
     * @param string $controller
     * @param $options
     * @return RouteCollection
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function resource($resourceDefinition, string $path, string $controller, array $options): static
    {
        $resourceDefinitionFactory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($resourceDefinition);

        $only = $options[self::OPTIONS_ONLY_INCLUDE_METHODS] ?? [
            self::OPTIONS_METHOD_INDEX,
            self::OPTIONS_METHOD_VIEW,
            self::OPTIONS_METHOD_STORE,
            self::OPTIONS_METHOD_EDIT,
            self::OPTIONS_METHOD_DESTROY
        ];

        $id = $options[self::OPTIONS_IDENTIFIER_NAME] ?? 'id';
        $maxExpandDepth = $options[self::OPTIONS_MAX_EXPAND_DEPTH] ?? 2;

        $group = $this->group([]);

        if (in_array(self::OPTIONS_METHOD_INDEX, $only)) {
            $group->get($path, $controller . '@index', [], 'index')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->returns()->statusCode(200)->many($resourceDefinitionFactory->getDefault())
                ->maxExpandDepth($maxExpandDepth);
        }

        if (in_array(self::OPTIONS_METHOD_VIEW, $only)) {
            $viewRoute = $group->get($path . '/{' . $id . '}', $controller . '@view', [], 'view')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault())
                ->maxExpandDepth($maxExpandDepth);

            $this->addIdParameterToRoutePath($viewRoute, $id, $options);
        }

        if (in_array(self::OPTIONS_METHOD_STORE, $only)) {
            $group->post($path, $controller . '@store', [], 'store')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault())
                ->maxExpandDepth($maxExpandDepth);
        }

        if (in_array(self::OPTIONS_METHOD_EDIT, $only)) {
            $editRoute = $group->put($path . '/{' . $id . '}', $controller . '@edit', [], 'edit')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault())
                ->maxExpandDepth($maxExpandDepth);

            $this->addIdParameterToRoutePath($editRoute, $id, $options);
        }

        if (in_array(self::OPTIONS_METHOD_PATCH, $only)) {
            $patchRoute = $group->patch($path . '/{' . $id . '}', $controller . '@patch', [], 'patch')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Patch an existing ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault())
                ->maxExpandDepth($maxExpandDepth);

            $this->addIdParameterToRoutePath($patchRoute, $id, $options);
        }

        if (in_array(self::OPTIONS_METHOD_DESTROY, $only)) {
            $deleteRoute = $group->delete($path . '/{' . $id . '}', $controller . '@destroy', [], 'destroy')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Delete a ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
            ;

            $this->addIdParameterToRoutePath($deleteRoute, $id, $options);
        }

        return $group;
    }

    /**
     * @param Route $route
     * @param $idName
     * @param array $options
     * @return void
     */
    private function addIdParameterToRoutePath(RouteMutator $route, $idName, array $options): void
    {
        $idParameter = $route->parameters()->path($idName)->string()->required();
        if (isset($options[self::OPTIONS_IDENTIFIER_TRANSFORMER])) {
            $idParameter->transformer($options[self::OPTIONS_IDENTIFIER_TRANSFORMER]);
        }
    }

    /**
     * Set all crud actions, including documentation
     * Really only usable for the default case
     * @param $resourceDefinition
     * @param string $parentPath
     * @param string $childPath
     * @param string $controller
     * @param array $options
     * @return RouteCollection
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function childResource($resourceDefinition, $parentPath, string $childPath, string $controller, array $options): static
    {
        $resourceDefinitionFactory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($resourceDefinition);

        $id = $options[self::OPTIONS_IDENTIFIER_NAME] ?? 'id';
        $parentId = $options[self::OPTIONS_PARENT_IDENTIFIER_NAME] ?? 'parentId';

        //$only = $options['only'] ?? [ 'index', 'view', 'store', 'edit', 'destroy' ];
        $only = $options[self::OPTIONS_ONLY_INCLUDE_METHODS] ?? [
            self::OPTIONS_METHOD_INDEX,
            self::OPTIONS_METHOD_VIEW,
            self::OPTIONS_METHOD_STORE,
            self::OPTIONS_METHOD_EDIT,
            self::OPTIONS_METHOD_DESTROY
        ];

        $group = $this->group([]);

        if (in_array(self::OPTIONS_METHOD_INDEX, $only)) {
            $indexRoute = $group->get($parentPath, $controller . '@index', [], 'index')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->returns()->statusCode(200)->many($resourceDefinitionFactory->getDefault());

            $this->addIdParameterToRoutePath($indexRoute, $parentId, $options);
        }

        if (in_array(self::OPTIONS_METHOD_VIEW, $only)) {
            $viewRoute = $group->get($childPath . '/{' . $id . '}', $controller . '@view', [], 'view')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());

            $this->addIdParameterToRoutePath($viewRoute, $id, $options);
        }

        if (in_array(self::OPTIONS_METHOD_STORE, $only)) {
            $storeRoute = $group->post($parentPath, $controller . '@store', [], 'store')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());

            $this->addIdParameterToRoutePath($storeRoute, $parentId, $options);
        }

        if (in_array(self::OPTIONS_METHOD_EDIT, $only)) {
            $editRoute = $group->put($childPath . '/{' . $id . '}', $controller . '@edit', [], 'edit')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());

            $this->addIdParameterToRoutePath($editRoute, $id, $options);
        }

        if (in_array(self::OPTIONS_METHOD_DESTROY, $only)) {
            $destroyRoute = $group->delete($childPath . '/{' . $id . '}', $controller . '@destroy', [], 'destroy')
                ->summary(function () use ($resourceDefinitionFactory): string {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Delete a ' . $entityName;
                });

            $this->addIdParameterToRoutePath($destroyRoute, $id, $options);
        }

        return $group;
    }

    /**
     * Return a flat list of routes
     * @return Route[]
     */
    public function getRoutes(): array
    {
        $out = [];
        $out = $this->routes;

        foreach ($this->children as $child) {
            foreach ($child->getRoutes() as $route) {
                $out[] = $route;
            }
        }

        return $out;
    }

    /**
     * Get routes within this collection that match the action.
     * @param $action
     * @return array
     */
    public function getFromAction($action): array
    {
        $out = [];
        foreach ($this->routes as $route) {
            if ($route->getAction() === $action) {
                $out[] = $route;
            }
        }

        return $out;
    }

    /**
     * @param $path
     * @param $method
     * @return Route|null
     */
    public function getFromPath($path, $method)
    {
        foreach ($this->routes as $route) {
            if ($route->getPath() !== $path) {
                continue;
            }

            if (strtoupper($route->getMethod()) !== strtoupper($method)) {
                continue;
            }

            return $route;
        }

        return null;
    }

    /**
     * Find a route based on method and path.
     * @param $path
     * @param $method
     * @return MatchedRoute|null
     */
    public function findFromPath($path, $method = null)
    {
        foreach ($this->getRoutes() as $route) {
            if ($matchedRoute = $route->matches($path, $method)) {
                return $matchedRoute;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $out = "";
        foreach ($this->getRoutes() as $route) {
            $out .= str_pad(strtoupper($route->getMethod()), 8, ' ');
            $out .= str_pad($route->getPath(), 70, ' ');
            $out .= $route->getAction();

            $out .= "\n";
        }

        return $out;
    }

    /**
     * @param $method
     * @param $path
     * @param $action
     * @param $options
     * @return Route
     */
    protected function createRoute($method, $path, $action, $options): \CatLab\Charon\Models\Routing\Route
    {
        return new Route($this, $method, $path, $action, $options);
    }

    /**
     * @param $options
     * @return $this
     */
    protected function createRouteCollection($options): static
    {
        return new static($options);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if (is_string($offset)) {
            return isset($this->namedRoutesMap[$offset]);
        }

        return isset($this->routes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|void
     * @throws NotImplementedException
     */
    public function offsetGet($offset): mixed
    {
        if (is_string($offset)) {
            return $this->namedRoutesMap[$offset];
        }

        return $this->routes[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws NotImplementedException
     */
    public function offsetSet($offset, $value): void
    {
        throw NotImplementedException::makeTranslatable('Cannot set routes this way; use action()');
    }

    /**
     * @param mixed $offset
     * @throws NotImplementedException
     */
    public function offsetUnset($offset): void
    {
        throw NotImplementedException::makeTranslatable('Cannot unset routes.');
    }
}
