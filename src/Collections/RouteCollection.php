<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Method;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Interfaces\Documentation\DocumentationVisitor;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
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
    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var RouteCollection[]
     */
    private $children;

    /**
     * Helper to access routes in a collection more dynamically.
     * @var array
     */
    private $namedRoutesMap;

    /**
     * RouteCollection constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->routes = [];
        $this->children = [];
        $this->namedRoutesMap = [];
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
    public function resource($resourceDefinition, $path, $controller, $options)
    {
        $resourceDefinitionFactory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($resourceDefinition);

        $id = $options['id'] ?? 'id';
        $only = $options['only'] ?? [ 'index', 'view', 'store', 'edit', 'destroy' ];

        $group = $this->group([]);

        if (in_array('index', $only)) {
            $group->get($path, $controller . '@index', [], 'index')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->returns()->statusCode(200)->many($resourceDefinitionFactory->getDefault());
        }

        if (in_array('view', $only)) {
            $group->get($path . '/{' . $id . '}', $controller . '@view', [], 'view')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('store', $only)) {
            $group->post($path, $controller . '@store', [], 'store')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('edit', $only)) {
            $group->put($path . '/{' . $id . '}', $controller . '@edit', [], 'edit')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('patch', $only)) {
            $group->patch($path . '/{' . $id . '}', $controller . '@patch', [], 'patch')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Patch an existing ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('destroy', $only)) {
            $group->delete($path . '/{' . $id . '}', $controller . '@destroy', [], 'destroy')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

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
     * @param string $parentPath
     * @param string $childPath
     * @param string $controller
     * @param array $options
     * @return RouteCollection
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function childResource($resourceDefinition, $parentPath, $childPath, $controller, $options)
    {
        $resourceDefinitionFactory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($resourceDefinition);

        $id = $options['id'] ?? 'id';
        $parentId = $options['parentId'] ?? 'parentId';

        $only = $options['only'] ?? [ 'index', 'view', 'store', 'edit', 'destroy' ];

        $group = $this->group([]);

        if (in_array('index', $only)) {
            $group->get($parentPath, $controller . '@index', [], 'index')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(true);
                    return 'Returns all ' . $entityName;
                })
                ->parameters()->path($parentId)->string()->required()
                ->returns()->statusCode(200)->many($resourceDefinitionFactory->getDefault());
        }

        if (in_array('view', $only)) {
            $group->get($childPath . '/{' . $id . '}', $controller . '@view', [], 'view')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'View a single ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('store', $only)) {
            $group->post($parentPath, $controller . '@store', [], 'store')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Create a new ' . $entityName;
                })
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->parameters()->path($parentId)->string()->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('edit', $only)) {
            $group->put($childPath . '/{' . $id . '}', $controller . '@edit', [], 'edit')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

                    return 'Update an existing ' . $entityName;
                })
                ->parameters()->path($id)->string()->required()
                ->parameters()->resource($resourceDefinitionFactory->getDefault())->required()
                ->returns()->statusCode(200)->one($resourceDefinitionFactory->getDefault());
        }

        if (in_array('destroy', $only)) {
            $group->delete($childPath . '/{' . $id . '}', $controller . '@destroy', [], 'destroy')
                ->summary(function () use ($resourceDefinitionFactory) {
                    $entityName = $resourceDefinitionFactory->getDefault()->getEntityName(false);

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
     * Get routes within this collection that match the action.
     * @param $action
     * @return array
     */
    public function getFromAction($action)
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
            if (
                $route->getPath() === $path &&
                strtoupper($route->getMethod()) === strtoupper($method)
            ) {
                return $route;
            }
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

    /**
     * @param $method
     * @param $path
     * @param $action
     * @param $options
     * @return Route
     */
    protected function createRoute($method, $path, $action, $options)
    {
        return new Route($this, $method, $path, $action, $options);
    }

    /**
     * @param $options
     * @return $this
     */
    protected function createRouteCollection($options)
    {
        return new static($options);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (is_string($offset)) {
            return isset($this->namedRoutesMap[$offset]);
        } else {
            return isset($this->routes[$offset]);
        }
    }

    /**
     * @param mixed $offset
     * @return mixed|void
     * @throws NotImplementedException
     */
    public function offsetGet($offset)
    {
        if (is_string($offset)) {
            return $this->namedRoutesMap[$offset];
        } else {
            return $this->routes[$offset];
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws NotImplementedException
     */
    public function offsetSet($offset, $value)
    {
        throw new NotImplementedException('Cannot set routes this way; use action()');
    }

    /**
     * @param mixed $offset
     * @throws NotImplementedException
     */
    public function offsetUnset($offset)
    {
        throw new NotImplementedException('Cannot unset routes.');
    }
}
