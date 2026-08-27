<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * CrudController::patch() has existed for as long as edit() has, and
 * RouteCollection knew the verb, but childResource() never registered a route
 * for it - so child resources had no partial-update verb at all. That is why a
 * PUT to one had to re-send every required relationship just to change a single
 * field.
 */
final class PatchRouteTest extends BaseTest
{
    private function methodsFor(RouteCollection $routes, string $path): array
    {
        $methods = [];
        foreach ($routes->getRoutes() as $route) {
            if ($route->getPath() === $path) {
                $methods[] = strtolower($route->getHttpMethod());
            }
        }

        sort($methods);
        return $methods;
    }

    public function testChildResourceRegistersAPatchRoute(): void
    {
        $routes = new RouteCollection();
        $routes->childResource(
            ResourceDefinition::class,
            'parents/{parentId}/children',
            'children',
            'ChildController',
            []
        );

        $this->assertContains('patch', $this->methodsFor($routes, 'children/{id}'));
    }

    public function testTopLevelResourceRegistersAPatchRoute(): void
    {
        $routes = new RouteCollection();
        $routes->resource(ResourceDefinition::class, 'things', 'ThingController', []);

        $this->assertContains('patch', $this->methodsFor($routes, 'things/{id}'));
    }

    public function testPatchRoutePointsAtTheControllersPatchAction(): void
    {
        $routes = new RouteCollection();
        $routes->childResource(
            ResourceDefinition::class,
            'parents/{parentId}/children',
            'children',
            'ChildController',
            []
        );

        $patch = null;
        foreach ($routes->getRoutes() as $route) {
            if (strtolower($route->getHttpMethod()) === 'patch') {
                $patch = $route;
            }
        }

        $this->assertNotNull($patch);
        $this->assertStringEndsWith('ChildController@patch', $patch->getAction());
    }

    /**
     * `only` still wins - an application that lists its verbs explicitly keeps
     * exactly those.
     */
    public function testOnlyStillExcludesPatch(): void
    {
        $routes = new RouteCollection();
        $routes->childResource(
            ResourceDefinition::class,
            'parents/{parentId}/children',
            'children',
            'ChildController',
            [ 'only' => [ 'index', 'view' ] ]
        );

        // `view` still puts a GET there; `patch` must not appear.
        $this->assertSame([ 'get' ], $this->methodsFor($routes, 'children/{id}'));
    }
}
