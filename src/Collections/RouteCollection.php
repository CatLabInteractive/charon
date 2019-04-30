<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Method;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Routing\MatchedRoute;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Models\Routing\RouteProperties;

/**
 * Class RouteCollection
 * @package CatLab\RESTResource\src\CatLab\RESTResource\Collections
 */
class RouteCollection extends RouteProperties
{
    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var RouteCollection[]
     */
    private $children;

    /**
     * RouteCollection constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->routes = [];
        $this->children = [];
    }

    /**
     * @param $options
     * @param callable $callback
     * @return RouteCollection
     */
    public function group($options, callable $callback = null)
    {
        if (!isset($callback) && is_callable($options)) {
            $callback = $options;
            $options = [];
        }

        $child = new self($options);
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
     * @return Route
     */
    public function get($path, $action, array $options = [])
    {
        return $this->action('get', $path, $action, $options);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @return Route
     */
    public function post($path, $action, array $options = [])
    {
        return $this->action('post', $path, $action, $options);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @return Route
     */
    public function put($path, $action, array $options = [])
    {
        return $this->action('put', $path, $action, $options);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @return Route
     */
    public function patch($path, $action, array $options = [])
    {
        return $this->action('patch', $path, $action, $options);
    }

    /**
     * @param string $path
     * @param string|callable $action
     * @param array $options
     * @return Route
     */
    public function delete($path, $action, array $options = [])
    {
        return $this->action('delete', $path, $action, $options);
    }

    /**
     * Represents a request to link resources with eachother
     * @param $path
     * @param $action
     * @param array $options
     * @return Route
     */
    public function link($path, $action, array $options = [])
    {
        return $this->action(Method::LINK, $path, $action, $options);
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
     * @return Route
     */
    public function action($method, $path, $action, array $options = [])
    {
        $route = new Route($this, $method, $path, $action, $options);
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
     */
    public function resource($resourceDefinition, $path, $controller, $options)
    {
        $id = $options['id'] ?? 'id';
        $only = $options['only'] ?? [ 'index', 'view', 'store', 'edit', 'destroy' ];

        $group = $this->group([]);

        if (in_array('index', $only)) {
            $group->get($path, $controller . '@index')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->returns()->statusCode(200)->many($resourceDefinition);
        }

        if (in_array('view', $only)) {
            $group->get($path . '/{' . $id . '}', $controller . '@view')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('store', $only)) {
            $group->post($path, $controller . '@store')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinition)->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('edit', $only)) {
            $group->put($path . '/{' . $id . '}', $controller . '@edit')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->parameters()->resource($resourceDefinition)->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('destroy', $only)) {
            $group->delete($path . '/{' . $id . '}', $controller . '@destroy')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Delete a ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
            ;
        }

        return $group;
    }

    /**
     * Set all crud actions, including documentation
     * Really only usable for the default case
     * @param $resourceDefinition
     * @param $parentPath
     * @param $childPath
     * @param string $controller
     * @param $options
     * @return RouteCollection
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function childResource($resourceDefinition, $parentPath, $childPath, $controller, $options)
    {
        $id = $options['id'] ?? 'id';
        $parentId = $options['parentId'] ?? 'parentId';

        $only = $options['only'] ?? [ 'index', 'view', 'store', 'edit', 'destroy' ];

        $group = $this->group([]);

        if (in_array('index', $only)) {
            $group->get($parentPath, $controller . '@index')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->parameters()->path($parentId)->string()->required()
                ->returns()->statusCode(200)->many($resourceDefinition);
        }

        if (in_array('view', $only)) {
            $group->get($childPath . '/{' . $id . '}', $controller . '@view')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('store', $only)) {
            $group->post($parentPath, $controller . '@store')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinition)->required()
                ->parameters()->path($parentId)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('edit', $only)) {
            $group->put($childPath . '/{' . $id . '}', $controller . '@edit')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->parameters()->resource($resourceDefinition)->required()
                ->returns()->statusCode(200)->one($resourceDefinition);
        }

        if (in_array('destroy', $only)) {
            $group->delete($childPath . '/{' . $id . '}', $controller . '@destroy')
                ->summary(function () use ($resourceDefinition) {
                    $entityName = ResourceDefinitionLibrary::make($resourceDefinition)
                        ->getEntityName(false);

                    return 'Delete a ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
            ;
        }

        return $group;
    }

    /**
     * Return a flat list of routes
     * @return Route[]
     */
    public function getRoutes()
    {
        $out = [];
        foreach($this->routes as $route) {
            $out[] = $route;
        }

        foreach ($this->children as $child) {
            foreach ($child->getRoutes() as $route) {
                $out[] = $route;
            }
        }

        return $out;
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
    public function __toString()
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
}