<?php

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Enums\Action;
use Tests\Petstore\Definitions\PetDefinition;
use Tests\Petstore\Models\Pet;

$routes = new RouteCollection();

$routes->group(
    [
        'prefix' => '/api/v1/',
        'suffix' => '.{format?}',
        'namespace' => '\App\Petstore\Controllers'
    ],
    function(RouteCollection $routes) {

        $routes
            ->parameters()->path('format')->enum(['json'])->describe('Output format')->default('json');

        $routes->returns()->statusCode(403)->describe('Authentication error');
        $routes->returns()->statusCode(404)->describe('Entity not found');

        // Swagger description
        $routes
            ->get('', 'DescriptionController@description')
            ->summary('Get swagger API description')
            ->tag('swagger');

        // Pets
        $routes
            ->get('pets', 'PetController@index')
            ->summary('Get all pet')
            ->parameters()->query('name')->describe('Find pets on name')
            ->parameters()->query('status')->enum([
                Pet::STATUS_AVAILABLE,
                Pet::STATUS_ENDING,
                Pet::STATUS_SOLD
            ])
            ->returns(PetDefinition::class, Action::VIEW)->many()
        ;

        $routes
            ->get('pets/{id}', 'PetController@show')
            ->summary('Get a pet')
            ->parameters()->path('id')->int()->required()
            ->returns(PetDefinition::class, Action::INDEX)->one()
        ;

        $routes
            ->put('pets/{id}', 'PetController@edit')
            ->summary('Update a pet')
            ->parameters()->path('id')->int()->required()
            ->parameters()->resource(PetDefinition::class)
            ->returns(PetDefinition::class, Action::INDEX)->one()
        ;
    }
);

return $routes;