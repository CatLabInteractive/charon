<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Transformers\BooleanTransformer;
use Tests\Petstore\Definitions\PetDefinition;

/**
 * resource() and childResource() are the entry point most applications use:
 * one call registers the whole CRUD surface of a resource. Getting a verb,
 * a path or an `only` filter wrong there silently removes an endpoint from an
 * API (that is exactly how child resources ended up with no PATCH verb), and
 * nothing exercised the verbs other than PATCH.
 */
final class RouteCollectionTest extends BaseTest
{
    /**
     * @return array<string, string> "METHOD path" => action
     */
    private function map(RouteCollection $routes): array
    {
        $out = [];
        foreach ($routes->getRoutes() as $route) {
            $out[strtoupper($route->getMethod()) . ' ' . $route->getPath()] = $route->getAction();
        }

        ksort($out);
        return $out;
    }

    public function testResourceRegistersTheFullCrudSurface(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', []);

        $this->assertSame(
            [
                'DELETE pets' => 'PetController@bulkDestroy',
                'DELETE pets/{id}' => 'PetController@destroy',
                'GET pets' => 'PetController@index',
                'GET pets/{id}' => 'PetController@view',
                'PATCH pets/{id}' => 'PetController@patch',
                'POST pets' => 'PetController@store',
                'PUT pets/{id}' => 'PetController@edit',
            ],
            $this->map($routes)
        );
    }

    /**
     * childResource() splits the surface across two paths: collection-level
     * verbs hang off the parent path (so they carry the parent id), item-level
     * verbs off the child path.
     */
    public function testChildResourceSplitsCollectionAndItemVerbsAcrossTheTwoPaths(): void
    {
        $routes = new RouteCollection();
        $routes->childResource(PetDefinition::class, 'users/{parentId}/pets', 'pets', 'PetController', []);

        $this->assertSame(
            [
                'DELETE pets/{id}' => 'PetController@destroy',
                'DELETE users/{parentId}/pets' => 'PetController@bulkDestroy',
                'GET pets/{id}' => 'PetController@view',
                'GET users/{parentId}/pets' => 'PetController@index',
                'PATCH pets/{id}' => 'PetController@patch',
                'POST users/{parentId}/pets' => 'PetController@store',
                'PUT pets/{id}' => 'PetController@edit',
            ],
            $this->map($routes)
        );
    }

    /**
     * `only` is a whitelist: nothing outside it may be registered.
     */
    public function testOnlyRegistersNothingButTheListedMethods(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', [
            RouteCollection::OPTIONS_ONLY_INCLUDE_METHODS => [ 'index', 'store' ],
        ]);

        $this->assertSame(
            [
                'GET pets' => 'PetController@index',
                'POST pets' => 'PetController@store',
            ],
            $this->map($routes)
        );
    }

    /**
     * `destroy` covers both the single delete and the bulk delete - asking for
     * one must not quietly leave out the other.
     */
    public function testDestroyRegistersBothTheSingleAndTheBulkDelete(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', [
            RouteCollection::OPTIONS_ONLY_INCLUDE_METHODS => [ 'destroy' ],
        ]);

        $this->assertSame(
            [
                'DELETE pets' => 'PetController@bulkDestroy',
                'DELETE pets/{id}' => 'PetController@destroy',
            ],
            $this->map($routes)
        );
    }

    /**
     * The identifier name is configurable, and it has to change the actual path
     * placeholder, not only the documented parameter.
     */
    public function testIdentifierNameOptionRenamesThePathPlaceholder(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', [
            RouteCollection::OPTIONS_IDENTIFIER_NAME => 'petId',
        ]);

        $paths = array_keys($this->map($routes));

        $this->assertContains('GET pets/{petId}', $paths);
        $this->assertContains('PATCH pets/{petId}', $paths);
        $this->assertNotContains('GET pets/{id}', $paths);
    }

    public function testParentIdentifierNameOptionRenamesTheParentPlaceholderParameter(): void
    {
        $routes = new RouteCollection();
        $routes->childResource(PetDefinition::class, 'users/{userId}/pets', 'pets', 'PetController', [
            RouteCollection::OPTIONS_PARENT_IDENTIFIER_NAME => 'userId',
        ]);

        $index = $this->routeFor($routes, 'get', 'users/{userId}/pets');

        $this->assertSame([ 'userId' ], $this->parameterNames($index));
    }

    /**
     * Every route that takes an id in its path must document that id as a
     * required path parameter - the description builders render straight off
     * these, so a missing one produces an undocumented (and untyped) endpoint.
     */
    public function testEveryIdentifiedRouteDocumentsItsIdPathParameter(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', []);

        foreach ($routes->getRoutes() as $route) {
            if (!str_contains($route->getPath(), '{id}')) {
                continue;
            }

            $this->assertContains(
                'id',
                $this->parameterNames($route),
                $route->getAction() . ' takes an id in its path but does not document it.'
            );
        }
    }

    /**
     * An identifier transformer (hashids, slugs, ...) has to be attached to
     * every route that receives that identifier, otherwise a subset of the CRUD
     * surface silently expects raw database ids.
     */
    public function testIdentifierTransformerIsAttachedToEveryIdentifiedRoute(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', [
            RouteCollection::OPTIONS_IDENTIFIER_TRANSFORMER => BooleanTransformer::class,
        ]);

        $identified = 0;
        foreach ($routes->getRoutes() as $route) {
            if (!str_contains($route->getPath(), '{id}')) {
                continue;
            }

            ++$identified;

            foreach ($route->getParameters() as $parameter) {
                if ($parameter->getName() === 'id') {
                    $classes = array_map('get_class', $parameter->getTransformers());
                    $this->assertContains(
                        BooleanTransformer::class,
                        $classes,
                        $route->getAction() . ' does not transform its identifier.'
                    );
                }
            }
        }

        $this->assertSame(4, $identified, 'view, edit, patch and destroy all take an identifier.');
    }

    /**
     * maxExpandDepth caps how deep a client may expand relationships. It is a
     * denial-of-service guard as much as a documentation detail, so it has to
     * reach the return values of the reading routes.
     */
    public function testMaxExpandDepthOptionReachesTheReturnValues(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', [
            RouteCollection::OPTIONS_MAX_EXPAND_DEPTH => 5,
        ]);

        $view = $this->routeFor($routes, 'get', 'pets/{id}');

        $this->assertSame(5, $view->getMaxExpandDepth());
    }

    /**
     * Routes registered by resource() are named, so an application can reach
     * back into them (to add a middleware, a tag, an extra parameter) without
     * scanning the list.
     */
    public function testResourceRoutesAreReachableByName(): void
    {
        $routes = new RouteCollection();
        $group = $routes->resource(PetDefinition::class, 'pets', 'PetController', []);

        $this->assertTrue(isset($group['view']));
        $this->assertFalse(isset($group['nonexistent']));
        $this->assertSame('PetController@view', $group['view']->getAction());
        $this->assertSame('PetController@patch', $group['patch']->getAction());
    }

    /**
     * Routes may only be added through the action() helpers, which is what
     * attaches them to their collection - array assignment would produce a
     * route with no parent and no documentation.
     */
    public function testRoutesCannotBeAssignedOrUnsetThroughArrayAccess(): void
    {
        $routes = new RouteCollection();
        $routes->get('pets', 'PetController@index', [], 'index');

        try {
            $routes['index'] = null;
            $this->fail('Expected assignment to be rejected.');
        } catch (NotImplementedException) {
            // expected
        }

        $this->expectException(NotImplementedException::class);
        unset($routes['index']);
    }

    /**
     * findFromPath() is how an incoming request is mapped back onto a route; it
     * has to pick the right one for the method and hand back the path
     * parameters it captured.
     */
    public function testFindFromPathMatchesTheRouteAndExtractsPathParameters(): void
    {
        $routes = new RouteCollection();
        $routes->resource(PetDefinition::class, 'pets', 'PetController', []);

        $matched = $routes->findFromPath('pets/12', 'get');

        $this->assertNotNull($matched);
        $this->assertSame('view', $matched->getControllerAction());
        $this->assertSame([ '12' ], $matched->getParameters());

        $this->assertSame('patch', $routes->findFromPath('pets/12', 'patch')->getControllerAction());
        $this->assertSame('index', $routes->findFromPath('pets', 'get')->getControllerAction());
        $this->assertNull($routes->findFromPath('pets/12/photos', 'get'));
    }

    /**
     * group() nests collections; getRoutes() has to walk the whole tree, since
     * that is what a framework iterates to register everything.
     */
    public function testNestedGroupsAreFlattenedByGetRoutes(): void
    {
        $routes = new RouteCollection();
        $routes->group([], function (RouteCollection $group): void {
            $group->get('pets', 'PetController@index');
            $group->group([], function (RouteCollection $inner): void {
                $inner->get('pets/{id}/photos', 'PhotoController@index');
            });
        });

        $this->assertSame(
            [
                'GET pets' => 'PetController@index',
                'GET pets/{id}/photos' => 'PhotoController@index',
            ],
            $this->map($routes)
        );
    }

    private function routeFor(RouteCollection $routes, string $method, string $path): Route
    {
        foreach ($routes->getRoutes() as $route) {
            if ($route->getPath() === $path && strtolower($route->getMethod()) === $method) {
                return $route;
            }
        }

        $this->fail('No ' . strtoupper($method) . ' route registered for ' . $path);
    }

    /**
     * @return string[]
     */
    private function parameterNames(Route $route): array
    {
        return array_map(fn ($parameter): string => $parameter->getName(), $route->getParameters());
    }
}
