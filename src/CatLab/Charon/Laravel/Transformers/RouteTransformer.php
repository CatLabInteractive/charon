<?php

namespace CatLab\Charon\Laravel\Transformers;

use CatLab\Charon\Collections\RouteCollection;
use Route;

/**
 * Class RouteTransformer
 * @package CatLab\RESTResource\Laravel\Transformers
 */
class RouteTransformer
{
    /**
     * RouteTransformer constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param RouteCollection $routes
     * @return void
     */
    public function transform(RouteCollection $routes)
    {
        foreach ($routes->getRoutes() as $route) {
            $options = $route->getOptions();
            $action = $route->getAction();

            $laravelRoute = Route::match([ $route->getHttpMethod() ], $route->getPath(), $action);

            if (isset($options['middleware'])) {
                $laravelRoute->middleware($options['middleware']);
            }
        }
    }
}