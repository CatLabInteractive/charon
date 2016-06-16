<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Enums\Method;
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
     * @return string
     */
    public function __toString()
    {
        $out = "";
        foreach ($this->getRoutes() as $route) {
            $out .= str_pad(strtoupper($route->getMethod()), 6, ' ');
            $out .= str_pad($route->getPath(), 70, ' ');
            $out .= $route->getAction();

            $out .= "\n";
        }

        return $out;
    }
}