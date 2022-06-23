---
id: input-parsing
title: Input parsing
sidebar_label: Input parsing
---

:::info
While Charon was built framework agnostic, in this documentation we assume you are
using our [Laravel branch](https://github.com/catlabinteractive/charon-laravel).
:::

## Input to Resource
In order to translate API input (probably JSON, but could be anything depending on your implementation) into resources,
you must set an `InputParser` in your `Context`. The `InputParser` will read content from the request body and translate
it to a Resource.

```php
$context = new Context(Action::WRITE);
$context->addInputParser(JsonBodyInputParser::class);

$inputResources = $this->resourceTransformer->fromInput(
    $this->getResourceDefinition(),
    $writeContext,
    $request
);
```

Note that the transformer returns a collection of `Resources`.

## Input validation
(Look at the next page about Validation to learn how to set this works. For now, let's just get to it.)

```php
foreach ($inputResources as $inputResource) {
    try {
        $inputResource->validate($writeContext);

        // also see if we can create this entity.
        $this->authorizeCreateFromResource($request, $inputResource);

    } catch (ResourceValidationException $e) {
        return $this->getValidationErrorResponse($e);
    }
}
```

## Transform resources to Entities
The magic here happens in the `PropertySetter` class, which is the opposite of the `PropertyGetter` as it sets data
instead of getting it. Note that we create a second ResourceCollection that holds a new set of Resources that is generated
from the newly generated entities, in an INDEX context.

```php
$indexContext = new Context(Action::INDEX);
$createdResources = new ResourceCollection();

foreach ($inputResources as $inputResource) {
    $entity = $this->toEntity($inputResource, $writeContext);
    $entity->save();

    $createdResources->add($this->toResource($entity, $readContext));
}

return new ResourceResponse($createdResources, $readContext);
```

And there we have it: input transformed to resources, saved in entities and then transformed back into resources.
