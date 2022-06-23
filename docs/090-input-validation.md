---
id: input-validation
title: Input validation
sidebar_label: Input validation
---

:::info
While Charon was built framework agnostic, in this documentation we assume you are
using our [Laravel branch](https://github.com/catlabinteractive/charon-laravel).
:::

Input validation
================
All generated resources have a 'validate' method that checks if the provided content matches the validation rules.
The `validate()` method takes a `Context` object to specify which validation rules should be checked. The `validate()`
method throws a `ResourceValidationException` that can be transformed in an array of validation error messages.

```php
public function store(Request $request)
{
    $writeContext = $this->getContext(Action::CREATE);
    $inputResource = $this->bodyToResource($writeContext);

    try {
        $inputResource->validate($writeContext);

        // also see if we can create this entity.
        $this->authorizeCreateFromResource($request, $inputResource);

    } catch (ResourceValidationException $e) {
        return $this->getValidationErrorResponse($e);
    }

    [...]
}

/**
 * @param ResourceValidationException $e
 * @return \Symfony\Component\HttpFoundation\Response
 */
protected function getValidationErrorResponse(ResourceValidationException $e)
{
    $errors = [];
    foreach ($e->getMessages()->toMap() as $fieldErrorMessages) {
        foreach ($fieldErrorMessages as $fieldErrorMessage) {

            $errors[] = [
                'title' => 'Could not decode resource.',
                'detail' => $fieldErrorMessage
            ];
        }
    }

    return Response::json([ 'errors' => $errors ])
        ->header('Content-type', 'application/vnd.api+json')
        ->setStatusCode(422);
}

```

:::tip
This functionality is readily available in the `CrudController` trait that can be used in your Controller
:::

Validation rules
----------------
The default field validation rules are simple:

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
                ->string()
                ->required()
                ->visible(true)
                
            ->field('status')
                ->enum([ 'available', 'reserved', 'sold' ])
                ->visible(true)
                
            ->field('colors')
                ->enum([ 'black', 'white', 'brown', 'purple' ])
                ->required()
                ->allowMultiple()
                ->visible(true);
    }
}
```

This will create a Pet resource where:
 * 'name' must be provided and must be a string
 * 'status' is optionally provided, but when provided must be one of the provided enum values
 * 'colors' is required and must be an array where all values must be in the provided enum values list

Custom validators
-----------------
In order to implement more complex validation rules, a `Validator` may be provided.

```php
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
            
            ->relationship('photos', PhotoDefinition::class)
                ->many()
                ->visible()
                ->expandable()
                ->writeable()
                ->url('/api/v1/pets/{model.id}/photos')

            ->validator(new PetValidator())
        ;
    }
}
```

```php
class PetValidator implements Validator
{

    /**
     * @param $value
     * @return mixed
     * @throws RequirementValidationException
     */
    public function validate($value)
    {
        /** @var RESTResource $value */

        // A pet must have at least one picture.
        $photos = $value->getProperties()->getFromName('photos')->getValue();

        if ($photos === null || count($photos) < 2) {
            throw ValidatorValidationException::make($this, $value);
        }
    }

    /**
     * @param ValidatorValidationException $exception
     * @return Message
     */
    public function getErrorMessage(ValidatorValidationException $exception) : Message
    {
        return new Message('Pets must have at least one photo.');
    }
}
```

