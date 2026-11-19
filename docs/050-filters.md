---
id: filters
title: Filters
sidebar_label: Filters
---

:::info
While Charon was built framework agnostic, in this documentation we assume you are 
using our [Laravel branch](https://github.com/catlabinteractive/charon-laravel).
:::

Filters
=======
Resource definitions can declare resource fields to be 'filterable' and 'sortable'. Fields that are marked filterable 
can be used in as filters in any 'index' request (either root elements or child relationships). The filters are parsed
from the request according to the selected API description and the implementation of this parsing is implemented in 
a RequestResolver.

In order to apply the filters to a query builder like Eloquent, you need to use (or implement) a QueryAdapter.
If you are using Eloquent, you can use our default one.

Simple Example
--------------

Make a PetDefinition that contains a filterable 'name' field.

```php
/**
 * Class PetDefinition
 * @package CatLab\Petstore\Definitions
 */
class PetDefinition extends ResourceDefinition
{
    /**
     * PetDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Pet::class);
        
        $this
            ->identifier('id')
                ->int()
            
            ->field('name')
                ->writeable()
                ->required()
                ->visible(true)
                ->filterable();
        ;
    }
}
```

Operators
---------

A plain parameter filters on equality: `GET /pets?name=Bolt`. To filter otherwise, pass an array with an operator
(`op`) and, where the operator compares with something, a `value`:

| Request | Matches |
|---|---|
| `?name[op]=eq&name[value]=Bolt` | name is `Bolt` (same as `?name=Bolt`) |
| `?name[op]=neq&name[value]=Bolt` | name is not `Bolt` |
| `?name[op]=contains&name[value]=ol` | name contains `ol` |
| `?name[op]=empty` | name is null or an empty string |
| `?name[op]=not_empty` | name is set and not an empty string |

An unknown operator, or one that needs a value without one, filters nothing. The operators live in
`CatLab\Charon\Enums\FilterOperator`; a QueryAdapter receives `Operator::EQ`, `Operator::NEQ`, `Operator::SEARCH`,
`FilterOperator::EMPTY` or `FilterOperator::NOT_EMPTY`.

A simplified controller would look like this:

```php
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
        if ($filterResults->isReversed()) {
            $models = $models->reverse();
        }

        // And now convert these models to resources
        $resources = $transformer->toResources($models, $context, $resourceDefinition, $filterResults);
        return new JsonApiResponse($resources, $context);
    }
}

```

In our [Laravel package](https://github.com/catlabinteractive/charon-laravel) you can find our CrudController trait 
that implements all this functionality by default. This implements a default method for all CRUD methods and also 
implements Laravel policy checks.

Our CrudController also has a method `applyGlobalFilters()` that can be used to define filters that will be called 
for every collection.
