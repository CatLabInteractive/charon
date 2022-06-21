<?php

namespace App\Petstore\Controllers;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Laravel\JsonApi\Models\JsonApiResponse;
use CatLab\Charon\Laravel\Models\ModelFilterResults;
use CatLab\Charon\Models\Context;
use CatLab\Charon\SimpleResolvers\SimpleResourceTransformer;

class SimplePetController
{
    public function index(\Illuminate\Http\Request $request)
    {
        $transformer = new SimpleResourceTransformer();
        $resourceDefinition = new PetDefinition();
        $context = new Context(Action::INDEX);

        // First load the filters so that we can use these in the policy
        $filters = $transformer->getFilters(
            $request->query(),
            $resourceDefinition,
            $context
        );

        $queryBuilder = Pet::query();

        // Filter the results
        $filterResults = $transformer->applyFilters($request->query(), $filters, $context, $queryBuilder);

        // Handle eager loading
        $transformer->processEagerLoading($queryBuilder, $resourceDefinition, $context);

        // Actually process the query
        $models = $queryBuilder->get();

        // Reverse if required... yes, I know this is a bit silly
        if ($filterResults && $filterResults->isReversed()) {
            $models = $models->reverse();
        }

        // And now convert these models to resources
        $resources = $transformer->toResources($models, $context, $resourceDefinition, $filterResults);
        return new JsonApiResponse($resources, $context);
    }
}
